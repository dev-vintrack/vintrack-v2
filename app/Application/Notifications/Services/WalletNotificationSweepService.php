<?php

namespace App\Application\Notifications\Services;

use App\Infrastructure\Persistence\Models\NotificationPolicy;
use App\Infrastructure\Persistence\Models\UserProviderWallet;

class WalletNotificationSweepService
{
    public function __construct(
        private readonly CustomerMailNotificationService $notifications
    ) {
    }

    public function sendExpiringWarnings(): int
    {
        $sentCandidates = 0;
        $maxDays = NotificationPolicy::where('event_type', NotificationPolicy::EXPIRING)
            ->where('enabled', true)
            ->get()
            ->flatMap(fn (NotificationPolicy $policy) => $policy->expiring_days ?? [])
            ->map(fn ($day) => (int) $day)
            ->max() ?? 7;

        $wallets = UserProviderWallet::with(['user', 'service.provider'])
            ->where('status', 'active')
            ->where('balance', '>', 0)
            ->whereNotNull('validity_end')
            ->where('validity_end', '>', now())
            ->where('validity_end', '<=', now()->addDays(max(1, $maxDays)))
            ->get();

        foreach ($wallets as $wallet) {
            if (! $wallet->service || ! $wallet->validity_end) {
                continue;
            }

            $policy = NotificationPolicy::resolveFor($wallet->service, NotificationPolicy::EXPIRING);
            $days = collect($policy->expiring_days ?? [7, 3, 1])
                ->map(fn ($day) => (int) $day)
                ->filter(fn ($day) => $day > 0)
                ->unique()
                ->values();
            $secondsRemaining = max(1, now()->diffInSeconds($wallet->validity_end, false));
            $daysRemaining = max(1, (int) ceil($secondsRemaining / 86400));

            if (! $days->contains($daysRemaining)) {
                continue;
            }

            $this->notifications->notifyExpiring($wallet, $daysRemaining);
            $sentCandidates++;
        }

        return $sentCandidates;
    }
}
