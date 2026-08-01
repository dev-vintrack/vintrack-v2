<?php

namespace Tests\Feature\Notifications;

use App\Application\Notifications\Services\CustomerMailNotificationService;
use App\Application\Notifications\Services\WalletNotificationSweepService;
use App\Domain\Consultas\Entities\Consultation;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use App\Infrastructure\Persistence\Models\NotificationDelivery;
use App\Infrastructure\Persistence\Models\NotificationPolicy;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use App\Mail\ExpiredCreditsMail;
use App\Mail\ExpiringCreditsMail;
use App\Mail\LowCreditMail;
use App\Mail\PlacasAlertMail;
use App\Mail\ZeroCreditMail;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerMailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_balance_is_policy_aware_tracked_and_deduplicated(): void
    {
        Mail::fake();
        [$user, $service, $wallet] = $this->createWallet(5, now()->addDays(30));
        NotificationPolicy::resolveFor($service, NotificationPolicy::LOW_BALANCE)->update([
            'enabled' => true,
            'low_balance_threshold' => 10,
            'cooldown_hours' => 72,
        ]);

        $notifications = app(CustomerMailNotificationService::class);
        $notifications->notifyBalanceAfterDebit($user->id, $service->id, 5, 'debit-one');
        $notifications->notifyBalanceAfterDebit($user->id, $service->id, 4, 'debit-two');

        Mail::assertSent(LowCreditMail::class, 1);
        Mail::assertNotSent(ZeroCreditMail::class);
        $this->assertDatabaseCount('notification_deliveries', 1);
        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $user->id,
            'provider_service_id' => $service->id,
            'event_type' => NotificationPolicy::LOW_BALANCE,
            'status' => 'sent',
        ]);
    }

    public function test_disabled_policy_is_tracked_as_skipped_without_sending_mail(): void
    {
        Mail::fake();
        [$user, $service] = $this->createWallet(4, now()->addDays(30));
        NotificationPolicy::resolveFor($service, NotificationPolicy::LOW_BALANCE)->update([
            'enabled' => false,
            'low_balance_threshold' => 5,
        ]);

        app(CustomerMailNotificationService::class)
            ->notifyBalanceAfterDebit($user->id, $service->id, 4, 'disabled-low-balance');

        Mail::assertNothingSent();
        $this->assertDatabaseHas('notification_deliveries', [
            'event_type' => NotificationPolicy::LOW_BALANCE,
            'status' => 'skipped',
        ]);
    }

    public function test_zero_balance_has_priority_over_low_balance(): void
    {
        Mail::fake();
        [$user, $service] = $this->createWallet(0, now()->addDays(30));

        app(CustomerMailNotificationService::class)
            ->notifyBalanceAfterDebit($user->id, $service->id, 0, 'debit-to-zero');

        Mail::assertSent(ZeroCreditMail::class, 1);
        Mail::assertNotSent(LowCreditMail::class);
        $this->assertDatabaseHas('notification_deliveries', [
            'event_type' => NotificationPolicy::ZERO_BALANCE,
            'status' => 'sent',
        ]);
        $this->assertDatabaseMissing('notification_deliveries', [
            'event_type' => NotificationPolicy::LOW_BALANCE,
        ]);
    }

    public function test_expiring_warning_is_sent_once_for_configured_day(): void
    {
        Mail::fake();
        [, $service] = $this->createWallet(20, now()->addDays(3));
        NotificationPolicy::resolveFor($service, NotificationPolicy::EXPIRING)->update([
            'enabled' => true,
            'expiring_days' => [7, 3, 1],
        ]);

        $sweep = app(WalletNotificationSweepService::class);
        $sweep->sendExpiringWarnings();
        $sweep->sendExpiringWarnings();

        Mail::assertSent(ExpiringCreditsMail::class, 1);
        $this->assertDatabaseCount('notification_deliveries', 1);
        $this->assertDatabaseHas('notification_deliveries', [
            'event_type' => NotificationPolicy::EXPIRING,
            'status' => 'sent',
        ]);
    }

    public function test_expired_notification_is_tracked_without_zero_balance_duplicate(): void
    {
        Mail::fake();
        [, , $wallet] = $this->createWallet(12, now()->subMinute());
        $expiredAt = $wallet->validity_end->copy();

        $notifications = app(CustomerMailNotificationService::class);
        $notifications->notifyExpired($wallet, 12, $expiredAt);
        $notifications->notifyExpired($wallet, 12, $expiredAt);

        Mail::assertSent(ExpiredCreditsMail::class, 1);
        Mail::assertNotSent(ZeroCreditMail::class);
        $this->assertDatabaseHas('notification_deliveries', [
            'event_type' => NotificationPolicy::EXPIRED,
            'status' => 'sent',
        ]);
    }

    public function test_placas_risk_alert_keeps_admin_bcc_and_is_deduplicated(): void
    {
        Mail::fake();
        [$user, $service] = $this->createWallet(20, now()->addDays(30));
        NotificationPolicy::resolveFor($service, NotificationPolicy::RISK_ALERT)->update([
            'enabled' => true,
            'bcc_email' => 'alertas@vintrack.test',
        ]);
        $response = new ConsultationResponse(
            true,
            200,
            null,
            ['repuve' => [['estatus' => 'ROBO']]],
            'risk-api-id',
            ['repuve_robo' => 1]
        );
        $consultation = Consultation::fromResponse(
            $user->id,
            $service->provider_id,
            'placa',
            'ABC1234',
            [$service->key],
            1,
            $response,
            new DateTimeImmutable('2026-07-27 18:00:00')
        );

        $notifications = app(CustomerMailNotificationService::class);
        $notifications->notifyRiskAlert($user->id, $service->id, $consultation);
        $notifications->notifyRiskAlert($user->id, $service->id, $consultation);

        Mail::assertSent(PlacasAlertMail::class, function (PlacasAlertMail $mail) {
            return $mail->adminBcc === 'alertas@vintrack.test';
        });
        Mail::assertSent(PlacasAlertMail::class, 1);
        $this->assertDatabaseHas('notification_deliveries', [
            'event_type' => NotificationPolicy::RISK_ALERT,
            'bcc' => 'alertas@vintrack.test',
            'status' => 'sent',
        ]);
    }

    private function createWallet(float $balance, $validityEnd): array
    {
        $user = User::factory()->create([
            'email' => fake()->unique()->safeEmail(),
            'activo' => true,
            'status' => 'active',
        ]);
        $provider = Provider::create([
            'code' => 'TEST-' . fake()->unique()->numerify('#####'),
            'adapter_code' => 'test-' . fake()->unique()->numerify('#####'),
            'name' => 'Proveedor de prueba',
            'base_url' => 'https://example.test',
            'enabled' => true,
        ]);
        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'key' => 'SERVICE-' . fake()->unique()->numerify('#####'),
            'service_code' => 'service-' . fake()->unique()->numerify('#####'),
            'name' => 'Servicio de prueba',
            'credit_cost' => 1,
            'available_credits' => 1000,
            'min_alert_client' => 5,
            'min_alert_admin' => 5,
            'enabled' => true,
        ]);
        $wallet = UserProviderWallet::create([
            'user_id' => $user->id,
            'provider_id' => $provider->id,
            'provider_service_id' => $service->id,
            'balance' => $balance,
            'min_alert' => 5,
            'validity_start' => now()->subDay(),
            'validity_end' => $validityEnd,
            'status' => 'active',
        ]);

        return [$user, $service, $wallet];
    }
}
