<?php

namespace Tests\Feature\NotificationCases;

use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use App\Infrastructure\Persistence\Models\Consultation;
use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerNotificationCasePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_lists_only_own_cases_with_status_and_deadline(): void
    {
        [$owner, $other, $provider, $service] = $this->baseline();
        $own = $this->makeCase($owner, $provider, $service);
        $foreign = $this->makeCase($other, $provider, $service, '1HGCM82633A123457');

        $this->actingAs($owner)->get(route('customer.notification-cases.index'))
            ->assertOk()->assertSee($own->case_number)->assertSee('PENDIENTE')->assertSee('15/08/2026 23:59:59')
            ->assertDontSee($foreign->case_number);
    }

    public function test_non_customer_and_disabled_menu_are_denied(): void
    {
        [$owner] = $this->baseline();
        $admin = User::factory()->create(['rol' => 'admin']);
        $this->actingAs($admin)->get(route('customer.notification-cases.index'))->assertForbidden();
        \DB::table('customer_menu_permissions')->where('id_rol', $owner->id_rol)->where('route_name', 'customer.notification-cases.index')->update(['enabled' => false]);
        $this->actingAs($owner)->get(route('customer.notification-cases.index'))->assertForbidden();
    }

    public function test_detail_update_submit_and_documents_reject_cross_client_idor(): void
    {
        [$owner, $other, $provider, $service] = $this->baseline();
        $case = $this->makeCase($owner, $provider, $service);
        foreach (['show', 'update', 'submit'] as $action) {
            $request = $this->actingAs($other);
            $response = $action === 'show'
                ? $request->get(route("customer.notification-cases.$action", $case))
                : $request->post(route("customer.notification-cases.$action", $case), ['_method' => $action === 'update' ? 'PUT' : 'POST']);
            $action === 'show' ? $response->assertNotFound() : $response->assertForbidden();
        }
        $this->actingAs($other)->get(route('notification-cases.documents.index', $case))->assertForbidden();
    }

    public function test_draft_normalizes_text_rejects_future_and_ignores_server_owned_payload(): void
    {
        CarbonImmutable::setTestNow('2026-08-13 12:00:00');
        [$owner, , $provider, $service] = $this->baseline();
        $case = $this->makeCase($owner, $provider, $service);
        $payload = ['lock_version' => 0, 'request_key' => 'draft-http-1', 'recovery_place' => 'México Núñez', 'recovered_at' => '2026-08-13T11:59',
            'vin' => 'FORGED', 'user_id' => 999, 'status' => 'VALIDATED', 'notification_deadline_at' => '2030-01-01'];
        $this->actingAs($owner)->put(route('customer.notification-cases.update', $case), $payload)->assertRedirect();
        $case->refresh();
        $this->assertSame('MEXICO NUNEZ', $case->recovery_place);
        $this->assertSame('1HGCM82633A123456', $case->vin);
        $this->assertSame($owner->id, $case->user_id);
        $this->assertSame(NotificationCaseStatus::PENDING, $case->status);
        $this->assertSame('2026-08-15 23:59:59', $case->notification_deadline_at->format('Y-m-d H:i:s'));

        $this->actingAs($owner)->put(route('customer.notification-cases.update', $case), ['lock_version' => 1, 'request_key' => 'draft-http-2', 'recovered_at' => '2026-08-13T12:01'])
            ->assertSessionHasErrors('recovered_at');
        $this->assertSame('2026-08-13 11:59:00', $case->fresh()->recovered_at->format('Y-m-d H:i:s'));
        CarbonImmutable::setTestNow();
    }

    public function test_submit_accepts_iph_or_nuc_and_is_read_only_afterward(): void
    {
        CarbonImmutable::setTestNow('2026-08-13 12:00:00');
        [$owner, , $provider, $service] = $this->baseline();
        $case = $this->makeCase($owner, $provider, $service);
        $case->forceFill($this->completeFields(['iph' => null, 'nuc' => 'NUC-1']))->save();
        $this->actingAs($owner)->post(route('customer.notification-cases.submit', $case), ['lock_version' => 0, 'request_key' => 'submit-http-1', 'confirm_submission' => '1'])->assertRedirect();
        $this->assertSame(NotificationCaseStatus::SUBMITTED, $case->fresh()->status);
        $this->actingAs($owner)->put(route('customer.notification-cases.update', $case), ['lock_version' => 1, 'request_key' => 'late-edit', 'make' => 'OTRA'])->assertSessionHasErrors('case');
        $this->actingAs($owner)->get(route('customer.notification-cases.show', $case))->assertOk()->assertSee('modo solo lectura')->assertDontSee('Guardar borrador');
        CarbonImmutable::setTestNow();
    }

    public function test_submit_rejects_missing_iph_and_nuc_and_future_value_even_if_service_is_called_directly(): void
    {
        CarbonImmutable::setTestNow('2026-08-13 12:00:00');
        [$owner, , $provider, $service] = $this->baseline();
        $case = $this->makeCase($owner, $provider, $service);
        $case->forceFill($this->completeFields(['iph' => null, 'nuc' => null]))->save();
        $this->actingAs($owner)->post(route('customer.notification-cases.submit', $case), ['lock_version' => 0, 'request_key' => 'submit-http-none', 'confirm_submission' => '1'])->assertSessionHasErrors('case');
        $case->forceFill(['recovered_at' => CarbonImmutable::parse('2026-08-13 12:00:01'), 'iph' => 'IPH-1'])->save();
        $this->actingAs($owner)->post(route('customer.notification-cases.submit', $case), ['lock_version' => 0, 'request_key' => 'submit-http-future', 'confirm_submission' => '1'])->assertSessionHasErrors('case');
        CarbonImmutable::setTestNow();
    }

    public function test_all_canonical_states_render_with_server_side_capabilities(): void
    {
        [$owner, , $provider, $service] = $this->baseline();
        foreach (NotificationCaseStatus::cases() as $index => $status) {
            $case = $this->makeCase($owner, $provider, $service, '1HGCM82633A1234'.str_pad((string) (56 + $index), 3, '0', STR_PAD_LEFT));
            $case->forceFill(['status' => $status])->save();
            $response = $this->actingAs($owner)->get(route('customer.notification-cases.show', $case))->assertOk()->assertSee($status->label());
            $status->isEditableByOwner() ? $response->assertSee('Guardar borrador') : $response->assertDontSee('Guardar borrador');
        }
    }

    private function baseline(): array
    {
        $owner = User::factory()->create(['rol' => 'oficial', 'activo' => true, 'status' => 'active', 'approved_at' => now()]);
        $other = User::factory()->create(['rol' => 'oficial', 'activo' => true, 'status' => 'active', 'approved_at' => now()]);
        $provider = Provider::create(['code' => 'VINDATA', 'adapter_code' => 'vindata', 'name' => 'VINData', 'base_url' => 'https://example.test', 'enabled' => true]);
        $service = ProviderService::create(['provider_id' => $provider->id, 'key' => 'VHR', 'service_code' => 'vhr', 'name' => 'VHR', 'credit_cost' => 1, 'available_credits' => 100, 'enabled' => true]);

        return [$owner, $other, $provider, $service];
    }

    private function makeCase(User $user, Provider $provider, ProviderService $service, string $vin = '1HGCM82633A123456'): NotificationCase
    {
        $consultation = Consultation::create(['user_id' => $user->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id, 'criterio' => 'vin', 'valor' => $vin, 'services' => ['vhr'], 'success' => true, 'alerta_robo' => true]);
        $case = new NotificationCase;
        $case->forceFill(['consultation_id' => $consultation->id, 'user_id' => $user->id, 'case_number' => sprintf('NT-2026-%06d', $consultation->id), 'vin' => $vin, 'vin_key' => $vin, 'status' => NotificationCaseStatus::PENDING, 'notification_deadline_at' => CarbonImmutable::parse('2026-08-15 23:59:59'), 'opened_at' => now(), 'auto_close_at' => now()->addDays(30), 'lock_version' => 0, 'creation_key' => 'portal:'.$consultation->id])->save();

        return $case;
    }

    private function completeFields(array $overrides = []): array
    {
        return array_merge(['recovery_place' => 'LUGAR', 'country' => 'MEXICO', 'state' => 'PUEBLA', 'municipality' => 'PUEBLA', 'recovered_at' => CarbonImmutable::parse('2026-08-13 11:00:00'), 'license_plate' => 'ABC123', 'make' => 'HONDA', 'model_year' => 2020, 'origin' => 'NACIONAL', 'authority' => 'FISCALIA', 'investigation_file' => 'CI-1', 'safekeeping' => 'CORRALON', 'iph' => 'IPH-1'], $overrides);
    }
}
