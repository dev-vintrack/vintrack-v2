<?php

namespace Tests\Feature\NotificationCases;

use App\Application\Consultas\Exceptions\ConsultationOperationException;
use App\Application\Consultas\Services\ConsultationService;
use App\Application\Credits\CommandHandlers\DebitCreditsCommandHandler;
use App\Application\NotificationCases\Exceptions\ConsultationBlockedException;
use App\Application\NotificationCases\Services\NotificationCaseLifecycleService;
use App\Domain\Consultas\Repositories\ConsultationRepositoryInterface;
use App\Domain\Consultas\Services\ProviderAdapterInterface;
use App\Domain\Consultas\Services\ProviderAdapterRegistry;
use App\Domain\Consultas\Services\ProviderResultAssessor;
use App\Domain\Consultas\ValueObjects\ConsultationRequest;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use App\Infrastructure\Persistence\Models\Consultation;
use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCaseCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_pending_blocks_before_wallet_and_adapter(): void
    {
        [$user, $provider, $service] = $this->baseline();
        for ($i = 0; $i < 3; $i++) {
            $this->makeCase($user, $provider, $service, '1HGCM82633A12345'.$i);
        }
        $adapter = new CountingAdapter(false);
        $this->bindAdapter($adapter);

        try {
            app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'blocked-request');
            $this->fail('Expected consultation admission block.');
        } catch (ConsultationBlockedException $exception) {
            $this->assertSame(3, $exception->pendingCount);
        }

        $this->assertSame(0, $adapter->calls);
        $this->assertDatabaseCount('wallet_ledger', 0);
        $this->assertDatabaseCount('consultations', 3);
        $this->assertDatabaseCount('notification_case_events', 1);
        $this->assertDatabaseCount('notification_outbox', 1);
        $this->assertDatabaseHas('consultation_operations', ['status' => 'FAILED_RETRYABLE', 'failure_code' => 'ADMISSION_FAILED']);

        try {
            app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'blocked-request');
        } catch (ConsultationBlockedException) {
        }
        $this->assertDatabaseCount('notification_case_events', 1);
        $this->assertDatabaseCount('notification_outbox', 1);
    }

    public function test_positive_vin_consultation_creates_one_case_and_retry_reuses_creation(): void
    {
        [$user, $provider, $service] = $this->baseline();
        UserProviderWallet::create([
            'user_id' => $user->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id,
            'balance' => 5, 'status' => 'active', 'validity_start' => now()->subDay(), 'validity_end' => now()->addMonth(),
        ]);
        $adapter = new CountingAdapter(true);
        $this->bindAdapter($adapter);
        $result = app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'positive-request');

        $this->assertTrue($result->response()->success());
        $case = NotificationCase::firstOrFail();
        $this->assertSame($result->consultation()->id(), $case->consultation_id);
        $this->assertSame($user->id, $case->user_id);
        $this->assertSame('1HGCM82633A123456', $case->vin);
        $this->assertSame(NotificationCaseStatus::PENDING, $case->status);
        $this->assertMatchesRegularExpression('/^NT-\d{4}-\d{6}$/', $case->case_number);
        $this->assertDatabaseCount('notification_case_events', 1);
        $this->assertDatabaseCount('notification_outbox', 1);

        $replay = app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'positive-request');
        $this->assertSame($result->consultation()->id(), $replay->consultation()->id());
        $this->assertSame(1, $adapter->calls);
        $this->assertDatabaseCount('consultations', 1);
        $this->assertDatabaseCount('wallet_ledger', 1);
        $this->assertDatabaseHas('consultation_operations', ['status' => 'COMPLETED', 'consultation_id' => $result->consultation()->id()]);
    }

    public function test_nmvtis_recovered_theft_is_historical_and_never_creates_a_case_or_case_notifications(): void
    {
        [$user, $provider, $service] = $this->baseline('admin', 'nmvtis_plus');
        UserProviderWallet::create([
            'user_id' => $user->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id,
            'balance' => 5, 'status' => 'active', 'validity_start' => now()->subDay(), 'validity_end' => now()->addMonth(),
        ]);
        $adapter = new class implements ProviderAdapterInterface
        {
            public ?string $seenServiceCode = null;

            public function supports(string $adapterCode): bool
            {
                return strtolower($adapterCode) === 'vindata';
            }

            public function consult(ConsultationRequest $request): ConsultationResponse
            {
                $this->seenServiceCode = $request->serviceCode();
                $rawData = ['otherInformation' => [['event' => 'Recovered Theft']], 'reportSummary' => ['color' => 'red']];

                return new ConsultationResponse(
                    true,
                    200,
                    null,
                    ['vin' => $request->value(), 'rawData' => $rawData],
                    'nmvtis-fixture',
                    ['active_theft' => 0],
                    null,
                    app(ProviderResultAssessor::class)->assess('nmvtis_plus', $rawData),
                );
            }
        };
        $this->bindAdapter($adapter);

        $result = app(ConsultationService::class)->consult(
            $user->id,
            $provider->id,
            'vin',
            '1HGCM82633A123456',
            ['nmvtis_plus'],
            'nmvtis-recovered-theft',
        );

        $this->assertSame('nmvtis_plus', $adapter->seenServiceCode);
        $this->assertFalse($result->consultation()->alertaRobo());
        $this->assertSame('HISTORICAL_RECORD', $result->consultation()->flagsJson()['_provider_result_assessment']['classification']);
        $this->assertDatabaseCount('notification_cases', 0);
        $this->assertDatabaseCount('notification_case_events', 0);
        $this->assertDatabaseCount('notification_outbox', 0);
    }

    public function test_nmvtis_active_theft_creates_one_case_and_case_delivery_intents(): void
    {
        [$user, $provider, $service] = $this->baseline('admin', 'nmvtis_plus');
        UserProviderWallet::create([
            'user_id' => $user->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id,
            'balance' => 5, 'status' => 'active', 'validity_start' => now()->subDay(), 'validity_end' => now()->addMonth(),
        ]);
        $adapter = new class implements ProviderAdapterInterface
        {
            public function supports(string $adapterCode): bool
            {
                return strtolower($adapterCode) === 'vindata';
            }

            public function consult(ConsultationRequest $request): ConsultationResponse
            {
                $rawData = ['otherInformation' => [['event' => 'Active Theft']]];

                return new ConsultationResponse(
                    true,
                    200,
                    null,
                    ['vin' => $request->value(), 'rawData' => $rawData],
                    'nmvtis-active-fixture',
                    ['active_theft' => 1],
                    null,
                    app(ProviderResultAssessor::class)->assess('nmvtis_plus', $rawData),
                );
            }
        };
        $this->bindAdapter($adapter);

        $result = app(ConsultationService::class)->consult(
            $user->id,
            $provider->id,
            'vin',
            '1HGCM82633A123456',
            ['nmvtis_plus'],
            'nmvtis-active-theft',
        );

        $this->assertTrue($result->consultation()->alertaRobo());
        $this->assertSame('ACTIVE_QUALIFYING', $result->consultation()->flagsJson()['_provider_result_assessment']['classification']);
        $this->assertDatabaseCount('notification_cases', 1);
        $this->assertDatabaseCount('notification_case_events', 1);
        $this->assertDatabaseHas('notification_outbox', [
            'event_type' => 'CASE_CREATED',
            'channel' => 'PORTAL',
            'recipient_user_id' => $user->id,
        ]);
    }

    public function test_different_keys_are_distinct_and_key_cannot_be_reused_for_another_payload(): void
    {
        [$user, $provider, $service] = $this->baseline();
        UserProviderWallet::create([
            'user_id' => $user->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id,
            'balance' => 5, 'status' => 'active', 'validity_start' => now()->subDay(), 'validity_end' => now()->addMonth(),
        ]);
        $adapter = new CountingAdapter(false);
        $this->bindAdapter($adapter);

        app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'key-one');
        app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'key-two');

        $this->assertSame(2, $adapter->calls);
        $this->assertDatabaseCount('consultations', 2);
        $this->assertDatabaseCount('wallet_ledger', 2);

        $this->expectException(ConsultationOperationException::class);
        app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A654321', ['vhr'], 'key-one');
    }

    public function test_provider_exception_becomes_ambiguous_and_is_not_reinvoked(): void
    {
        [$user, $provider, $service] = $this->baseline();
        UserProviderWallet::create([
            'user_id' => $user->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id,
            'balance' => 5, 'status' => 'active', 'validity_start' => now()->subDay(), 'validity_end' => now()->addMonth(),
        ]);
        $adapter = new ThrowingAdapter;
        $this->bindAdapter($adapter);

        try {
            app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'ambiguous-key');
        } catch (\RuntimeException) {
        }

        try {
            app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'ambiguous-key');
            $this->fail('Ambiguous operation must not invoke the provider again.');
        } catch (ConsultationOperationException $exception) {
            $this->assertSame('IDEMPOTENCY_AMBIGUOUS', $exception->errorCode);
        }

        $this->assertSame(1, $adapter->calls);
        $this->assertDatabaseHas('consultation_operations', ['status' => 'FAILED_AMBIGUOUS']);
        $this->assertDatabaseCount('wallet_ledger', 0);
        $this->assertDatabaseCount('consultations', 0);
    }

    public function test_failure_before_provider_is_retryable_after_balance_recovery(): void
    {
        [$user, $provider, $service] = $this->baseline();
        $wallet = UserProviderWallet::create([
            'user_id' => $user->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id,
            'balance' => 0, 'status' => 'active', 'validity_start' => now()->subDay(), 'validity_end' => now()->addMonth(),
        ]);
        $adapter = new CountingAdapter(false);
        $this->bindAdapter($adapter);

        $first = app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'retryable-before-provider');
        $this->assertFalse($first->response()->success());
        $this->assertSame(0, $adapter->calls);
        $this->assertDatabaseHas('consultation_operations', ['status' => 'FAILED_RETRYABLE', 'failure_code' => 'INSUFFICIENT_BALANCE']);

        $wallet->forceFill(['balance' => 2])->save();
        $second = app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'retryable-before-provider');
        $this->assertTrue($second->response()->success());
        $this->assertSame(1, $adapter->calls);
        $this->assertDatabaseCount('wallet_ledger', 1);
        $this->assertDatabaseCount('consultations', 1);
        $this->assertDatabaseHas('consultation_operations', ['status' => 'COMPLETED', 'consultation_id' => $second->consultation()->id()]);
    }

    public function test_unequivocal_provider_failure_is_completed_and_replayed_without_debit(): void
    {
        [$user, $provider, $service] = $this->baseline();
        UserProviderWallet::create([
            'user_id' => $user->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id,
            'balance' => 5, 'status' => 'active', 'validity_start' => now()->subDay(), 'validity_end' => now()->addMonth(),
        ]);
        $adapter = new FailedResponseAdapter;
        $this->bindAdapter($adapter);

        $first = app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'failed-provider-response');
        $second = app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'failed-provider-response');

        $this->assertFalse($first->response()->success());
        $this->assertSame($first->consultation()->id(), $second->consultation()->id());
        $this->assertSame(1, $adapter->calls);
        $this->assertDatabaseCount('wallet_ledger', 0);
        $this->assertDatabaseCount('consultations', 1);
        $this->assertDatabaseHas('consultation_operations', ['status' => 'COMPLETED']);
    }

    public function test_debit_failure_after_provider_is_ambiguous(): void
    {
        [$user, $provider, $service] = $this->baseline();
        UserProviderWallet::create([
            'user_id' => $user->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id,
            'balance' => 5, 'status' => 'active', 'validity_start' => now()->subDay(), 'validity_end' => now()->addMonth(),
        ]);
        $adapter = new CountingAdapter(false);
        $this->bindAdapter($adapter);
        $debit = \Mockery::mock(DebitCreditsCommandHandler::class);
        $debit->shouldReceive('handle')->once()->andThrow(new \RuntimeException('Injected debit failure.'));
        $this->app->instance(DebitCreditsCommandHandler::class, $debit);
        $this->app->forgetInstance(ConsultationService::class);

        try {
            app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'debit-failure');
            $this->fail('Expected injected debit failure.');
        } catch (\RuntimeException) {
        }

        $this->assertSame(1, $adapter->calls);
        $this->assertDatabaseCount('wallet_ledger', 0);
        $this->assertDatabaseCount('consultations', 0);
        $this->assertDatabaseHas('consultation_operations', ['status' => 'FAILED_AMBIGUOUS', 'failure_code' => 'DEBIT_AFTER_PROVIDER_FAILED']);
    }

    public function test_consultation_persistence_failure_after_debit_is_ambiguous(): void
    {
        [$user, $provider, $service] = $this->baseline();
        UserProviderWallet::create([
            'user_id' => $user->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id,
            'balance' => 5, 'status' => 'active', 'validity_start' => now()->subDay(), 'validity_end' => now()->addMonth(),
        ]);
        $adapter = new CountingAdapter(false);
        $this->bindAdapter($adapter);
        $repository = \Mockery::mock(ConsultationRepositoryInterface::class);
        $repository->shouldReceive('save')->once()->andThrow(new \RuntimeException('Injected persistence failure.'));
        $this->app->instance(ConsultationRepositoryInterface::class, $repository);
        $this->app->forgetInstance(ConsultationService::class);

        try {
            app(ConsultationService::class)->consult($user->id, $provider->id, 'vin', '1HGCM82633A123456', ['vhr'], 'persistence-failure');
            $this->fail('Expected injected persistence failure.');
        } catch (\RuntimeException) {
        }

        $this->assertSame(1, $adapter->calls);
        $this->assertDatabaseCount('wallet_ledger', 1);
        $this->assertDatabaseCount('consultations', 0);
        $this->assertDatabaseHas('consultation_operations', ['status' => 'FAILED_AMBIGUOUS', 'failure_code' => 'CONSULTATION_PERSISTENCE_FAILED']);
    }

    public function test_plate_draft_assigns_vin_once_then_submits_with_iph_or_nuc(): void
    {
        [$owner, $provider, $service] = $this->baseline('oficial');
        $case = $this->makeCase($owner, $provider, $service, null, 'placa', 'ABC1234');
        $lifecycle = app(NotificationCaseLifecycleService::class);
        $case = $lifecycle->assignVinOnce($owner, $case->id, '1HGCM82633A123456', 'assign-1');
        $this->assertSame('1HGCM82633A123456', $case->vin);

        $case = $lifecycle->saveDraft($owner, $case->id, [
            'recovery_place' => 'Ángel Núñez', 'country' => 'México', 'state' => 'Puebla', 'municipality' => 'Puebla',
            'recovered_at' => '2026-08-12 10:00:00', 'license_plate' => 'abc-1234', 'make' => 'Honda', 'model_year' => 2020,
            'origin' => 'nacional', 'authority' => 'fiscalía', 'investigation_file' => 'ci-123', 'safekeeping' => 'corralón', 'nuc' => 'nuc-9',
        ], $case->lock_version, 'draft-1');
        $this->assertSame('ANGEL NUNEZ', $case->recovery_place);
        $submitted = $lifecycle->submit($owner, $case->id, $case->lock_version, 'submit-1');
        $this->assertSame(NotificationCaseStatus::SUBMITTED, $submitted->status);

        $this->expectException(DomainException::class);
        $lifecycle->assignVinOnce($owner, $case->id, '1HGCM82633A654321', 'assign-2');
    }

    public function test_analyst_must_start_review_before_validation_and_auto_close_is_idempotent(): void
    {
        [$owner, $provider, $service] = $this->baseline('oficial');
        $analyst = User::factory()->create(['rol' => 'analista']);
        $case = $this->makeCase($owner, $provider, $service, '1HGCM82633A123456');
        $case->forceFill(['status' => NotificationCaseStatus::SUBMITTED])->save();
        $lifecycle = app(NotificationCaseLifecycleService::class);

        $this->expectException(DomainException::class);
        try {
            $lifecycle->transition($analyst, $case->id, NotificationCaseStatus::VALIDATED, 0, 'invalid-direct');
        } finally {
            $case->refresh();
            $review = $lifecycle->transition($analyst, $case->id, NotificationCaseStatus::UNDER_REVIEW, $case->lock_version, 'review-1');
            $validated = $lifecycle->transition($analyst, $case->id, NotificationCaseStatus::VALIDATED, $review->lock_version, 'validate-1');
            $this->assertSame(NotificationCaseStatus::VALIDATED, $validated->status);

            $due = $this->makeCase($owner, $provider, $service, '1HGCM82633A123457');
            $due->forceFill(['auto_close_at' => now()->subSecond()])->save();
            $this->assertSame(1, $lifecycle->autoCloseDue());
            $this->assertSame(0, $lifecycle->autoCloseDue());
        }
    }

    public function test_vin_conflict_creates_one_reconciliation_and_does_not_assign(): void
    {
        [$owner, $provider, $service] = $this->baseline('oficial');
        $other = User::factory()->create(['rol' => 'oficial']);
        $existing = $this->makeCase($other, $provider, $service, '1HGCM82633A123456');
        $draft = $this->makeCase($owner, $provider, $service, null, 'placa', 'ABC-1234');
        $lifecycle = app(NotificationCaseLifecycleService::class);

        foreach (['assign-conflict', 'assign-conflict'] as $key) {
            try {
                $lifecycle->assignVinOnce($owner, $draft->id, '1HGCM82633A123456', $key);
            } catch (DomainException) {
            }
        }

        $this->assertNull($draft->fresh()->vin);
        $this->assertDatabaseCount('notification_case_vin_reconciliations', 1);
        $this->assertDatabaseCount('notification_case_events', 1);
        $this->assertDatabaseHas('notification_case_vin_reconciliations', ['notification_case_id' => $draft->id, 'conflicting_case_id' => $existing->id, 'status' => 'OPEN']);
    }

    public function test_cross_client_mutation_mass_assignment_and_manual_auto_close_are_denied(): void
    {
        [$owner, $provider, $service] = $this->baseline('oficial');
        $attacker = User::factory()->create(['rol' => 'oficial']);
        $admin = User::factory()->create(['rol' => 'admin']);
        $case = $this->makeCase($owner, $provider, $service, null, 'placa', 'ABC1234');
        $lifecycle = app(NotificationCaseLifecycleService::class);

        try {
            $lifecycle->saveDraft($attacker, $case->id, ['case_number' => 'FORGED', 'user_id' => $attacker->id, 'recovery_place' => 'AJENO'], 0, 'idor-1');
            $this->fail('Cross-client edit should fail.');
        } catch (DomainException) {
            $this->assertSame($owner->id, $case->fresh()->user_id);
            $this->assertNotSame('FORGED', $case->fresh()->case_number);
        }

        $case = $lifecycle->saveDraft($owner, $case->id, ['case_number' => 'FORGED', 'user_id' => $attacker->id, 'consultation_id' => 999, 'recovery_place' => 'PROPIO'], 0, 'mass-1');
        $this->assertSame($owner->id, $case->user_id);
        $this->assertNotSame('FORGED', $case->case_number);
        $this->assertSame('PROPIO', $case->recovery_place);

        $this->expectException(DomainException::class);
        $lifecycle->transition($admin, $case->id, NotificationCaseStatus::CLOSED_NO_FOLLOW_UP, $case->lock_version, 'manual-close');
    }

    private function baseline(string $role = 'admin', string $serviceCode = 'vhr'): array
    {
        $user = User::factory()->create(['rol' => $role, 'activo' => true, 'approved_at' => now()]);
        $provider = Provider::create(['code' => 'VINDATA', 'adapter_code' => 'vindata', 'name' => 'VINData', 'base_url' => 'https://example.test', 'enabled' => true]);
        $service = ProviderService::create(['provider_id' => $provider->id, 'key' => strtoupper($serviceCode), 'service_code' => $serviceCode, 'name' => strtoupper($serviceCode), 'credit_cost' => 1, 'available_credits' => 100, 'enabled' => true]);

        return [$user, $provider, $service];
    }

    private function makeCase(User $user, Provider $provider, ProviderService $service, ?string $vin, string $criterion = 'vin', ?string $value = null): NotificationCase
    {
        $value ??= $vin ?? 'ABC1234';
        $consultation = Consultation::create([
            'user_id' => $user->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id,
            'criterio' => $criterion, 'valor' => $value, 'services' => [$service->service_code], 'success' => true, 'alerta_robo' => true,
        ]);
        $case = new NotificationCase;
        $sequence = NotificationCase::count() + 1;
        $case->forceFill([
            'consultation_id' => $consultation->id, 'user_id' => $user->id, 'case_number' => sprintf('NT-2026-%06d', $sequence),
            'vin' => $vin, 'vin_key' => $vin, 'status' => NotificationCaseStatus::PENDING,
            'notification_deadline_at' => CarbonImmutable::parse('2026-08-15 23:59:59'), 'opened_at' => now(), 'auto_close_at' => now()->addDays(30),
            'lock_version' => 0, 'creation_key' => 'test:'.$consultation->id,
        ])->save();

        return $case;
    }

    private function bindAdapter(ProviderAdapterInterface $adapter): void
    {
        $this->app->singleton(ProviderAdapterRegistry::class, function () use ($adapter) {
            $registry = new ProviderAdapterRegistry;
            $registry->register($adapter);

            return $registry;
        });
        $this->app->forgetInstance(ConsultationService::class);
    }
}

final class ThrowingAdapter implements ProviderAdapterInterface
{
    public int $calls = 0;

    public function supports(string $adapterCode): bool
    {
        return strtolower($adapterCode) === 'vindata';
    }

    public function consult(ConsultationRequest $request): ConsultationResponse
    {
        $this->calls++;

        throw new \RuntimeException('Injected provider failure.');
    }
}

final class FailedResponseAdapter implements ProviderAdapterInterface
{
    public int $calls = 0;

    public function supports(string $adapterCode): bool
    {
        return strtolower($adapterCode) === 'vindata';
    }

    public function consult(ConsultationRequest $request): ConsultationResponse
    {
        $this->calls++;

        return new ConsultationResponse(false, 503, 'INJECTED_PROVIDER_FAILURE', [], null, []);
    }
}

final class CountingAdapter implements ProviderAdapterInterface
{
    public int $calls = 0;

    public function __construct(private readonly bool $qualifying) {}

    public function supports(string $adapterCode): bool
    {
        return strtolower($adapterCode) === 'vindata';
    }

    public function consult(ConsultationRequest $request): ConsultationResponse
    {
        $this->calls++;

        return new ConsultationResponse(true, 200, null, ['vin' => $request->value(), 'rawData' => []], 'test-api', [
            'active_theft' => $this->qualifying ? 1 : 0, 'open_lien' => 0, 'junk_salvage' => 0, 'odometer_issue' => 0,
        ]);
    }
}
