<?php

namespace Tests\Feature\ConsultationHistories;

use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use App\Infrastructure\Persistence\Models\Consultation;
use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsultationHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_origin_and_later_other_user_resolve_the_same_case_without_leaking_owner_details(): void
    {
        [$a, $b, $provider, $service] = $this->baseline();
        $origin = $this->consultation($a, $provider, $service, 'vin', '1HGCM82633A123456', '2026-01-01 10:00:00');
        $case = $this->case($origin, $a, NotificationCaseStatus::PENDING);
        $later = $this->consultation($b, $provider, $service, 'vin', '1HGCM82633A123456', '2026-01-10 10:00:00');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $own = $this->jsonFor($a, 'customer.consultations.data')->json('data.0');
        $this->assertLessThanOrEqual(12, count(DB::getQueryLog()), 'El endpoint no debe crecer con queries por fila (N+1).');
        DB::disableQueryLog();
        $this->assertSame($case->id, $own['action'] ? $case->id : null);
        $this->assertSame('OWN_CASE', $own['case_relation']);

        $other = $this->jsonFor($b, 'customer.consultations.data')->json('data.0');
        $this->assertSame($later->id, $other['consultation_id']);
        $this->assertSame('OTHER_USER_CASE', $other['case_relation']);
        $this->assertNull($other['action']);
        $this->assertArrayNotHasKey('case_owner_user_id', $other);
        $this->assertStringContainsString('OTRO USUARIO', $other['case_message']);
    }

    public function test_validated_case_reuses_within_ninety_days_and_new_case_after_window_wins(): void
    {
        [$a, $b, $provider, $service] = $this->baseline();
        $originA = $this->consultation($a, $provider, $service, 'vin', '1HGCM82633A123456', '2026-01-01 10:00:00');
        $caseA = $this->case($originA, $a, NotificationCaseStatus::VALIDATED, ['validated_at' => '2026-01-02 10:00:00']);
        $within = $this->consultation($b, $provider, $service, 'vin', '1HGCM82633A123456', '2026-04-02 10:00:00');
        $originB = $this->consultation($b, $provider, $service, 'vin', '1HGCM82633A123456', '2026-04-03 10:00:01');
        $caseB = $this->case($originB, $b, NotificationCaseStatus::PENDING, ['previous_case_id' => $caseA->id]);

        $rows = collect($this->jsonFor($b, 'customer.consultations.data', ['length' => 10])->json('data'))->keyBy('consultation_id');
        $this->assertSame('OTHER_USER_CASE', $rows[$within->id]['case_relation']);
        $this->assertSame('VALIDATED', $rows[$within->id]['general_status']);
        $this->assertSame('OWN_CASE', $rows[$originB->id]['case_relation']);
        $this->assertStringContainsString((string) $caseB->id, $rows[$originB->id]['action']['url']);
    }

    public function test_historical_consultation_is_never_linked_to_a_later_case(): void
    {
        [$a, , $provider, $service] = $this->baseline();
        $old = $this->consultation($a, $provider, $service, 'vin', '1HGCM82633A123456', '2025-12-01 10:00:00');
        $origin = $this->consultation($a, $provider, $service, 'vin', '1HGCM82633A123456', '2026-01-01 10:00:00');
        $this->case($origin, $a, NotificationCaseStatus::PENDING);

        $rows = collect($this->jsonFor($a, 'customer.consultations.data')->json('data'))->keyBy('consultation_id');
        $this->assertSame('NO_CASE', $rows[$old->id]['case_relation']);
        $this->assertSame('NO', $rows[$old->id]['notified_status']);
        $this->assertSame('NO APLICA', $rows[$old->id]['general_status']);
    }

    public function test_plate_without_vin_remains_unavailable_and_closed_notified_uses_events(): void
    {
        [$a, , $provider, $service] = $this->baseline();
        $origin = $this->consultation($a, $provider, $service, 'placa', 'ABC-123', '2026-01-01 10:00:00');
        $case = $this->case($origin, $a, NotificationCaseStatus::CLOSED_NO_FOLLOW_UP, ['vin' => null, 'vin_key' => null, 'closed_at' => '2026-01-20 10:00:00']);
        DB::table('notification_case_events')->insert(['notification_case_id' => $case->id, 'event_key' => 'submitted-history', 'event_type' => 'CASE_SUBMITTED', 'actor_type' => 'USER', 'occurred_at' => '2026-01-02 10:00:00', 'correlation_id' => 'history', 'created_at' => '2026-01-02 10:00:00']);

        $row = $this->jsonFor($a, 'customer.consultations.data')->json('data.0');
        $this->assertSame('VIN NO DISPONIBLE', $row['vin']);
        $this->assertSame('SI', $row['notified_status']);
        $this->assertSame('NO', $row['validated_status']);
    }

    public function test_customer_scope_and_admin_filter_pagination_search_and_adversarial_order_are_server_side(): void
    {
        [$a, $b, $provider, $service] = $this->baseline();
        $this->consultation($a, $provider, $service, 'vin', '1HGCM82633A123456', '2026-01-01 10:00:00');
        $this->consultation($b, $provider, $service, 'vin', '1HGCM82633A123457', '2026-01-02 10:00:00');
        $this->consultation($b, $provider, $service, 'vin', '1HGCM82633A123458', '2026-01-03 10:00:00');

        $customer = $this->jsonFor($a, 'customer.consultations.data', ['user_id' => $b->id]);
        $customer->assertStatus(400);
        $this->assertSame(1, $this->jsonFor($a, 'customer.consultations.data')->json('recordsTotal'));

        $analyst = User::factory()->create(['rol' => 'analista', 'activo' => true]);
        $page = $this->jsonFor($analyst, 'admin.consultations.data', ['user_id' => $b->id, 'start' => 0, 'length' => 1, 'draw' => 7, 'order' => [['column' => '0; DROP TABLE users', 'dir' => 'sideways']]]);
        $page->assertOk();
        $this->assertSame(7, $page->json('draw'));
        $this->assertSame(3, $page->json('recordsTotal'));
        $this->assertSame(2, $page->json('recordsFiltered'));
        $this->assertCount(1, $page->json('data'));
        $searched = $this->jsonFor($analyst, 'admin.consultations.data', ['search' => ['value' => '3458']]);
        $this->assertSame(1, $searched->json('recordsFiltered'));
        $this->assertDatabaseHas('users', ['id' => $a->id]);
    }

    public function test_derived_status_search_uses_the_latest_historically_applicable_case(): void
    {
        [$a, , $provider, $service] = $this->baseline();
        $first = $this->consultation($a, $provider, $service, 'vin', '1HGCM82633A123456', '2026-01-01 10:00:00');
        $this->case($first, $a, NotificationCaseStatus::REJECTED);
        $latest = $this->consultation($a, $provider, $service, 'vin', '1HGCM82633A123456', '2026-01-02 10:00:00');
        $this->case($latest, $a, NotificationCaseStatus::VALIDATED, ['validated_at' => '2026-01-03 10:00:00']);
        $analyst = User::factory()->create(['rol' => 'analista', 'activo' => true]);

        $validated = $this->jsonFor($analyst, 'admin.consultations.data', ['search' => ['value' => 'VALIDADO']]);
        $validated->assertOk();
        $this->assertSame(1, $validated->json('recordsFiltered'));
        $this->assertSame(['VALIDATED'], array_values(array_unique(array_column($validated->json('data'), 'general_status'))));

        $rejected = $this->jsonFor($analyst, 'admin.consultations.data', ['case_status' => 'REJECTED']);
        $this->assertSame(1, $rejected->json('recordsFiltered'));
    }

    public function test_vin_ordering_and_closed_without_submit_preserve_derived_semantics(): void
    {
        [$a, , $provider, $service] = $this->baseline();
        $plate = $this->consultation($a, $provider, $service, 'placa', 'ABC-123', '2026-01-01 10:00:00');
        $this->case($plate, $a, NotificationCaseStatus::CLOSED_NO_FOLLOW_UP, [
            'vin' => '1HGCM82633A000001', 'vin_key' => '1HGCM82633A000001', 'closed_at' => '2026-01-20 10:00:00',
        ]);
        $this->consultation($a, $provider, $service, 'vin', '9HGCM82633A000001', '2026-01-02 10:00:00');

        $response = $this->jsonFor($a, 'customer.consultations.data', [
            'length' => 10, 'order' => [['column' => 2, 'dir' => 'asc']],
        ]);
        $response->assertOk();
        $this->assertSame(['1HGCM82633A000001', '9HGCM82633A000001'], array_column($response->json('data'), 'vin'));
        $this->assertSame('NO', $response->json('data.0.notified_status'));
    }

    public function test_history_actions_expose_only_available_reports_and_preserve_case_actions(): void
    {
        [$owner, $other, $provider, $service] = $this->baseline();
        $report = $this->consultation($owner, $provider, $service, 'vin', '1HGCM82633A123456', '2026-01-01 10:00:00');
        $this->case($report, $owner, NotificationCaseStatus::PENDING);
        $missingReport = $this->consultation($owner, $provider, $service, 'vin', '1HGCM82633A123457', '2026-01-02 10:00:00', false);

        $customerRows = collect($this->jsonFor($owner, 'customer.consultations.data')->json('data'))->keyBy('consultation_id');
        $reportActions = $customerRows[$report->id]['actions'];

        $this->assertSame('report', $reportActions[0]['type']);
        $this->assertSame(route('reports.show', $report->id), $reportActions[0]['url']);
        $this->assertSame('notification_case', $reportActions[1]['type']);
        $this->assertSame([], $customerRows[$missingReport->id]['actions']);

        $admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        $otherReport = $this->consultation($other, $provider, $service, 'vin', '1HGCM82633A123458', '2026-01-03 10:00:00');
        $adminRows = collect($this->jsonFor($admin, 'admin.consultations.data', ['user_id' => $other->id])->json('data'))->keyBy('consultation_id');
        $this->assertSame(route('reports.show', $otherReport->id), $adminRows[$otherReport->id]['actions'][0]['url']);

        $this->actingAs($owner)->get(route('reports.show', $missingReport->id))->assertNotFound();
        $this->actingAs($owner)->get(route('reports.show', 999999))->assertNotFound();
    }

    private function jsonFor(User $user, string $route, array $parameters = [])
    {
        return $this->actingAs($user)->getJson(route($route, $parameters));
    }

    private function baseline(): array
    {
        $a = User::factory()->create(['rol' => 'oficial', 'activo' => true, 'status' => 'active', 'approved_at' => now()]);
        $b = User::factory()->create(['rol' => 'oficial', 'activo' => true, 'status' => 'active', 'approved_at' => now()]);
        $provider = Provider::create(['code' => 'H'.uniqid(), 'adapter_code' => 'test', 'name' => 'Provider', 'base_url' => 'https://example.test', 'enabled' => true]);
        $service = ProviderService::create(['provider_id' => $provider->id, 'key' => 'VHR', 'service_code' => 'vhr'.uniqid(), 'name' => 'VHR', 'credit_cost' => 1, 'available_credits' => 100, 'enabled' => true]);

        return [$a, $b, $provider, $service];
    }

    private function consultation(User $user, Provider $provider, ProviderService $service, string $criterion, string $value, string $at, bool $success = true): Consultation
    {
        $consultation = Consultation::create(['user_id' => $user->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id, 'criterio' => $criterion, 'valor' => $value, 'services' => ['vhr'], 'success' => $success, 'alerta_robo' => true]);
        $consultation->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

        return $consultation;
    }

    private function case(Consultation $consultation, User $owner, NotificationCaseStatus $status, array $overrides = []): NotificationCase
    {
        $case = new NotificationCase;
        $case->forceFill(array_merge(['consultation_id' => $consultation->id, 'user_id' => $owner->id, 'case_number' => sprintf('NT-2026-%06d', $consultation->id), 'vin' => $consultation->criterio === 'placa' ? null : $consultation->valor, 'vin_key' => $consultation->criterio === 'placa' ? null : $consultation->valor, 'license_plate' => $consultation->criterio === 'placa' ? $consultation->valor : 'ABC123', 'make' => 'HONDA', 'model' => 'CIVIC', 'model_year' => 2020, 'status' => $status, 'notification_deadline_at' => CarbonImmutable::parse($consultation->created_at)->addDays(3)->endOfDay(), 'opened_at' => $consultation->created_at, 'auto_close_at' => CarbonImmutable::parse($consultation->created_at)->addDays(30), 'lock_version' => 0, 'creation_key' => 'history:'.$consultation->id, 'created_at' => $consultation->created_at, 'updated_at' => $consultation->created_at], $overrides))->save();

        return $case;
    }
}
