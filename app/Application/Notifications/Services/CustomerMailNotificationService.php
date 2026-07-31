<?php

namespace App\Application\Notifications\Services;

use App\Domain\Consultas\Entities\Consultation;
use App\Infrastructure\Persistence\Models\NotificationDelivery;
use App\Infrastructure\Persistence\Models\NotificationPolicy;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use App\Mail\ExpiredCreditsMail;
use App\Mail\ExpiringCreditsMail;
use App\Mail\LowCreditMail;
use App\Mail\PlacasAlertMail;
use App\Mail\ZeroCreditMail;
use App\Models\User;
use App\Presentation\Support\PlacasReportPresenter;
use Carbon\CarbonInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class CustomerMailNotificationService
{
    public function notifyBalanceAfterDebit(
        int $userId,
        int $providerServiceId,
        float $balance,
        string $correlationId
    ): void {
        $wallet = UserProviderWallet::with(['user', 'service.provider'])
            ->where('user_id', $userId)
            ->where('provider_service_id', $providerServiceId)
            ->first();

        if (! $wallet || ! $wallet->service) {
            return;
        }

        if ($balance <= 0) {
            $this->sendZeroBalance($wallet, $correlationId);
            return;
        }

        $policy = NotificationPolicy::resolveFor($wallet->service, NotificationPolicy::LOW_BALANCE);
        $threshold = $policy->low_balance_threshold !== null
            ? (float) $policy->low_balance_threshold
            : (float) ($wallet->min_alert ?? $wallet->service->min_alert_client ?? config('vintrack.low_credit_threshold', 5));

        if ($balance > $threshold) {
            return;
        }

        $cooldown = max(1, (int) $policy->cooldown_hours);
        $bucket = (int) floor(now()->timestamp / ($cooldown * 3600));
        $dedupKey = "wallet.low_balance:{$wallet->id}:{$bucket}";
        $subject = 'VINTRACK: Tus créditos están por agotarse';

        $this->deliver(
            $policy,
            $wallet->user,
            $wallet->service,
            new LowCreditMail($wallet, $wallet->service, $balance, $threshold),
            $subject,
            $dedupKey,
            UserProviderWallet::class,
            $wallet->id,
            ['balance' => $balance, 'threshold' => $threshold, 'correlation_id' => $correlationId]
        );
    }

    public function notifyRiskAlert(int $userId, int $providerServiceId, Consultation $consultation): void
    {
        $user = User::find($userId);
        $service = ProviderService::with('provider')->find($providerServiceId);
        if (! $service) {
            return;
        }

        $policy = NotificationPolicy::resolveFor($service, NotificationPolicy::RISK_ALERT);
        $adminBcc = trim((string) ($policy->bcc_email ?: config('vintrack.admin_email', '')));
        $userInfo = [
            'email' => $user?->email ?? '',
            'nombre' => $user?->nombre ?? $user?->name ?? '',
            'telefono' => $user?->telefono ?? '',
            'rol' => $user?->rol ?? '',
        ];
        $consultationReference = $consultation->id() ?: sha1(
            $userId . '|' . $providerServiceId . '|' . $consultation->criterio() . '|' . $consultation->valor() . '|' . $consultation->createdAt()->format('c')
        );
        $subject = 'Alerta VINTRACK: posible reporte de robo o recuperado';

        $this->deliver(
            $policy,
            $user,
            $service,
            new PlacasAlertMail(
                $userInfo,
                $consultation->criterio(),
                $consultation->valor(),
                PlacasReportPresenter::sections($consultation->responseJson()),
                $adminBcc !== '' ? $adminBcc : null
            ),
            $subject,
            "consultation.risk_alert:{$consultationReference}",
            Consultation::class,
            $consultation->id(),
            ['theft_flags' => $consultation->theftFlags()],
            $adminBcc !== '' ? $adminBcc : null
        );
    }

    public function notifyExpiring(UserProviderWallet $wallet, int $daysRemaining): void
    {
        $wallet->loadMissing(['user', 'service.provider']);
        if (! $wallet->service || ! $wallet->validity_end) {
            return;
        }

        $policy = NotificationPolicy::resolveFor($wallet->service, NotificationPolicy::EXPIRING);
        $validityKey = $wallet->validity_end->format('YmdHis');
        $subject = "Tus créditos VINTRACK vencen en {$daysRemaining} " . ($daysRemaining === 1 ? 'día' : 'días');

        $this->deliver(
            $policy,
            $wallet->user,
            $wallet->service,
            new ExpiringCreditsMail($wallet, $wallet->service, $daysRemaining),
            $subject,
            "wallet.expiring:{$wallet->id}:{$validityKey}:{$daysRemaining}",
            UserProviderWallet::class,
            $wallet->id,
            ['balance' => (float) $wallet->balance, 'days_remaining' => $daysRemaining, 'validity_end' => $wallet->validity_end->toIso8601String()]
        );
    }

    public function notifyExpired(UserProviderWallet $wallet, float $amount, CarbonInterface $expiredAt): void
    {
        $wallet->loadMissing(['user', 'service.provider']);
        if (! $wallet->service) {
            return;
        }

        $policy = NotificationPolicy::resolveFor($wallet->service, NotificationPolicy::EXPIRED);
        $validityKey = $expiredAt->format('YmdHis');
        $subject = 'Tus créditos VINTRACK han vencido';

        $this->deliver(
            $policy,
            $wallet->user,
            $wallet->service,
            new ExpiredCreditsMail($wallet, $wallet->service, $amount, $expiredAt),
            $subject,
            "wallet.expired:{$wallet->id}:{$validityKey}",
            UserProviderWallet::class,
            $wallet->id,
            ['amount' => $amount, 'expired_at' => $expiredAt->toIso8601String()]
        );
    }

    private function sendZeroBalance(UserProviderWallet $wallet, string $correlationId): void
    {
        $policy = NotificationPolicy::resolveFor($wallet->service, NotificationPolicy::ZERO_BALANCE);
        $subject = 'VINTRACK: Tus créditos se han agotado';

        $this->deliver(
            $policy,
            $wallet->user,
            $wallet->service,
            new ZeroCreditMail($wallet, $wallet->service),
            $subject,
            "wallet.zero_balance:{$wallet->id}:{$correlationId}",
            UserProviderWallet::class,
            $wallet->id,
            ['balance' => 0, 'correlation_id' => $correlationId]
        );
    }

    private function deliver(
        NotificationPolicy $policy,
        ?User $user,
        ProviderService $service,
        Mailable $mail,
        string $subject,
        string $dedupKey,
        ?string $relatedType,
        ?int $relatedId,
        array $metadata,
        ?string $bcc = null
    ): bool {
        $delivery = NotificationDelivery::firstOrCreate(
            ['dedup_key' => $dedupKey],
            [
                'uuid' => (string) Str::uuid(),
                'user_id' => $user?->id,
                'provider_service_id' => $service->id,
                'event_type' => $policy->event_type,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'recipient' => $user?->email,
                'bcc' => $bcc,
                'subject' => $subject,
                'status' => 'pending',
                'attempts' => 0,
                'metadata' => $metadata,
            ]
        );

        if (! $delivery->wasRecentlyCreated) {
            return false;
        }

        if (! $policy->enabled) {
            $delivery->update(['status' => 'skipped', 'error' => 'Evento deshabilitado por política.']);
            return false;
        }

        if (! $user || empty($user->email)) {
            $delivery->update(['status' => 'skipped', 'error' => 'El usuario no tiene un correo disponible.']);
            return false;
        }

        try {
            $delivery->update(['attempts' => 1]);
            Mail::to($user->email)->send($mail);
            $delivery->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
            return true;
        } catch (Throwable $e) {
            $delivery->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error' => Str::limit($e->getMessage(), 2000, ''),
            ]);
            report($e);
            return false;
        }
    }
}
