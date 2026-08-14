<?php

namespace Tests\Feature\NotificationCases;

use App\Application\NotificationCases\Services\NotificationCaseLifecycleService;
use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use App\Infrastructure\Persistence\Models\Consultation;
use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminNotificationCasePortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_authorized_analyst_lists_filters_and_opens_global_cases_but_others_are_denied(): void
    {
        [$owner, $case] = $this->case(NotificationCaseStatus::SUBMITTED);
        $analyst = User::factory()->create(['rol' => 'analista', 'activo' => true]);
        $support = User::factory()->create(['rol' => 'soporte', 'activo' => true]);
        $this->actingAs($analyst)->get(route('admin.notification-cases.index', ['folio' => $case->case_number, 'user_id' => $owner->id, 'status' => 'SUBMITTED']))->assertOk()->assertSee($case->case_number)->assertSee($owner->email);
        $this->actingAs($analyst)->get(route('admin.notification-cases.index', ['vin' => 'NONEXISTENTVIN']))->assertOk()->assertDontSee($case->case_number);
        $this->actingAs($analyst)->get(route('admin.notification-cases.show', $case))->assertOk()->assertSee('Iniciar revisiÃ³n');
        $this->actingAs($support)->get(route('admin.notification-cases.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.notification-cases.index'))->assertForbidden();
    }

    public function test_start_review_and_admin_corrections_are_normalized_audited_and_mass_assignment_safe(): void
    {
        CarbonImmutable::setTestNow('2026-08-13 12:00:00');
        [$owner, $case] = $this->case(NotificationCaseStatus::SUBMITTED);
        $analyst = User::factory()->create(['rol' => 'analista', 'activo' => true]);
        $this->actingAs($analyst)->post(route('admin.notification-cases.start-review', $case), ['lock_version' => 0, 'request_key' => 'review-http'])->assertRedirect();
        $payload = ['lock_version' => 1, 'request_key' => 'correction-http', 'recovery_place' => '  mexico   nunez ', 'recovered_at' => '2026-08-13T11:30', 'vin' => 'FORGED', 'user_id' => 999, 'status' => 'VALIDATED', 'case_number' => 'FORGED'];
        $this->actingAs($analyst)->put(route('admin.notification-cases.update', $case), $payload)->assertRedirect();
        $case->refresh();
        $this->assertSame('MEXICO NUNEZ', $case->recovery_place);
        $this->assertSame($owner->id, $case->user_id);
        $this->assertSame('1HGCM82633A123456', $case->vin);
        $this->assertSame(NotificationCaseStatus::UNDER_REVIEW, $case->status);
        $this->assertDatabaseHas('notification_case_events', ['event_type' => 'ADMIN_FIELD_CORRECTED', 'field_name' => 'recovery_place', 'old_value' => 'LUGAR', 'new_value' => 'MEXICO NUNEZ']);
        $events = \DB::table('notification_case_events')->where('event_type', 'ADMIN_FIELD_CORRECTED')->count();
        $this->actingAs($analyst)->put(route('admin.notification-cases.update', $case), ['lock_version' => 2, 'request_key' => 'noop-http', 'recovery_place' => 'MEXICO NUNEZ'])->assertRedirect();
        $this->assertSame($events, \DB::table('notification_case_events')->where('event_type', 'ADMIN_FIELD_CORRECTED')->count());
        $this->assertSame(2, $case->fresh()->lock_version);
        CarbonImmutable::setTestNow();
    }

    public function test_future_recovery_and_incomplete_validation_are_rejected_server_side(): void
    {
        CarbonImmutable::setTestNow('2026-08-13 12:00:00');
        [, $case] = $this->case(NotificationCaseStatus::UNDER_REVIEW, ['authority' => null]);
        $analyst = User::factory()->create(['rol' => 'analista', 'activo' => true]);
        $this->actingAs($analyst)->put(route('admin.notification-cases.update', $case), ['lock_version' => 0, 'request_key' => 'future', 'recovered_at' => '2026-08-13T12:01'])->assertSessionHasErrors('recovered_at');
        $this->actingAs($analyst)->post(route('admin.notification-cases.validate', $case), ['lock_version' => 0, 'request_key' => 'invalid-validate'])->assertSessionHasErrors('case');
        $this->assertSame(NotificationCaseStatus::UNDER_REVIEW, $case->fresh()->status);
        CarbonImmutable::setTestNow();
    }

    public function test_rejection_requires_normalized_reason_preserves_case_and_is_visible_to_client_then_resubmits(): void
    {
        [$owner, $case] = $this->case(NotificationCaseStatus::UNDER_REVIEW);
        $analyst = User::factory()->create(['rol' => 'analista', 'activo' => true]);
        foreach ([null, '', '   '] as $index => $reason) {
            $this->actingAs($analyst)->post(route('admin.notification-cases.reject', $case), ['lock_version' => 0, 'request_key' => 'blank-'.$index, 'reason' => $reason])->assertSessionHasErrors('reason');
        }
        $this->actingAs($analyst)->post(route('admin.notification-cases.reject', $case), ['lock_version' => 0, 'request_key' => 'reject-one', 'reason' => '  falta   fotografia nitida  '])->assertRedirect();
        $case->refresh();
        $this->assertSame(NotificationCaseStatus::REJECTED, $case->status);
        $this->assertDatabaseHas('notification_case_events', ['notification_case_id' => $case->id, 'event_type' => 'CASE_REJECTED', 'reason' => 'FALTA FOTOGRAFIA NITIDA']);
        $this->actingAs($owner)->get(route('customer.notification-cases.show', $case))->assertOk()->assertSee('FALTA FOTOGRAFIA NITIDA')->assertSee('Motivo:');
        app(NotificationCaseLifecycleService::class)->submit($owner, $case->id, 1, 'resubmit-one');
        $this->assertSame(NotificationCaseStatus::SUBMITTED, $case->fresh()->status);
        $this->assertSame(1, \DB::table('notification_case_events')->where('notification_case_id', $case->id)->where('event_type', 'CASE_REJECTED')->count());
    }

    public function test_validate_is_explicit_terminal_admin_cannot_manage_evidence_and_manual_close_is_absent(): void
    {
        [, $case] = $this->case(NotificationCaseStatus::UNDER_REVIEW);
        $analyst = User::factory()->create(['rol' => 'analista', 'activo' => true]);
        $this->actingAs($analyst)->post(route('admin.notification-cases.validate', $case), ['lock_version' => 0, 'request_key' => 'validate-http'])->assertRedirect();
        $this->assertSame(NotificationCaseStatus::VALIDATED, $case->fresh()->status);
        $this->assertDatabaseHas('notification_case_events', ['event_type' => 'CASE_VALIDATED']);
        $this->assertDatabaseHas('notification_outbox', ['event_type' => 'CASE_VALIDATED', 'status' => 'PENDING']);
        $this->actingAs($analyst)->withHeader('Idempotency-Key', 'admin-upload')->post(route('notification-cases.documents.store', $case), ['document' => UploadedFile::fake()->createWithContent('x.pdf', "%PDF-1.4\n%%EOF")])->assertForbidden();
        $this->actingAs($analyst)->get(route('admin.notification-cases.show', $case))->assertOk()->assertDontSee('CERRADO POR FALTA DE SEGUIMIENTO', false)->assertDontSee('name="status"', false);
    }

    private function case(NotificationCaseStatus $status, array $overrides = []): array
    {
        $owner = User::factory()->create(['rol' => 'oficial', 'activo' => true, 'status' => 'active', 'approved_at' => now()]);
        $provider = Provider::create(['code' => 'P'.uniqid(), 'adapter_code' => 'test', 'name' => 'Provider', 'base_url' => 'https://example.test', 'enabled' => true]);
        $service = ProviderService::create(['provider_id' => $provider->id, 'key' => 'VHR', 'service_code' => 'vhr'.uniqid(), 'name' => 'VHR', 'credit_cost' => 1, 'available_credits' => 100, 'enabled' => true]);
        $consultation = Consultation::create(['user_id' => $owner->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id, 'criterio' => 'vin', 'valor' => '1HGCM82633A123456', 'services' => ['vhr'], 'success' => true, 'alerta_robo' => true]);
        $case = new NotificationCase;
        $case->forceFill(array_merge(['consultation_id' => $consultation->id, 'user_id' => $owner->id, 'case_number' => 'NT-2026-'.str_pad((string) $consultation->id, 6, '0', STR_PAD_LEFT), 'vin' => $consultation->valor, 'vin_key' => $consultation->valor, 'recovery_place' => 'LUGAR', 'country' => 'MEXICO', 'state' => 'PUEBLA', 'municipality' => 'PUEBLA', 'recovered_at' => now()->subHour(), 'license_plate' => 'ABC123', 'make' => 'HONDA', 'model_year' => 2020, 'origin' => 'NACIONAL', 'authority' => 'FISCALIA', 'iph' => 'IPH-1', 'investigation_file' => 'CI-1', 'safekeeping' => 'CORRALON', 'status' => $status, 'notification_deadline_at' => now()->addDays(3)->endOfDay(), 'opened_at' => now(), 'auto_close_at' => now()->addDays(30), 'lock_version' => 0, 'creation_key' => 'admin-test:'.$consultation->id], $overrides))->save();

        return [$owner, $case];
    }
}
