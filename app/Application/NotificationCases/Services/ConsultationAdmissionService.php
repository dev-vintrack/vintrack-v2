<?php

namespace App\Application\NotificationCases\Services;

use App\Application\NotificationCases\Exceptions\ConsultationBlockedException;
use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use Illuminate\Support\Facades\DB;

final class ConsultationAdmissionService
{
    public function __construct(
        private readonly NotificationCaseSettings $settings,
        private readonly NotificationCaseAuditService $audit,
        private readonly NotificationCaseOutboxService $outbox,
    ) {}

    public function reserve(int $userId, string $requestKey, ?string $ip = null, ?string $userAgent = null): int
    {
        $result = DB::transaction(function () use ($userId, $requestKey, $ip, $userAgent) {
            $existing = DB::table('notification_case_consultation_reservations')->where('request_key', $requestKey)->first();
            if ($existing) {
                if ($existing->consumed_at === null && $existing->released_at === null && $existing->expires_at >= now()->format('Y-m-d H:i:s.u')) {
                    return (int) $existing->id;
                }
                throw new ConsultationBlockedException($this->pendingCount($userId));
            }

            DB::table('notification_case_user_guards')->insertOrIgnore([
                'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('notification_case_user_guards')->where('user_id', $userId)->lockForUpdate()->first();

            $pending = $this->pendingCount($userId);
            $liveReservations = DB::table('notification_case_consultation_reservations')
                ->where('user_id', $userId)->whereNull('consumed_at')->whereNull('released_at')
                ->where('expires_at', '>', now())->count();

            if ($pending + $liveReservations >= $this->settings->maxPending()) {
                $eventKey = 'consultation-blocked:'.hash('sha256', $requestKey);
                $this->audit->record(null, 'CONSULTATION_BLOCKED', $eventKey, $requestKey, $userId, 'USER', null, null, null, [
                    'pending_count' => $pending,
                ], $requestKey, $ip, $userAgent);
                $this->outbox->queue(null, $userId, 'CONSULTATION_BLOCKED', 'PORTAL', $eventKey.':portal', [
                    'message' => 'MAX_PENDING_REACHED', 'pending_count' => $pending,
                ]);

                return ['blocked' => $pending];
            }

            return (int) DB::table('notification_case_consultation_reservations')->insertGetId([
                'user_id' => $userId,
                'request_key' => $requestKey,
                'expires_at' => now()->addSeconds($this->settings->reservationTtlSeconds()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);

        if (is_array($result)) {
            throw new ConsultationBlockedException($result['blocked']);
        }

        return $result;
    }

    public function consume(int $reservationId): void
    {
        DB::table('notification_case_consultation_reservations')->where('id', $reservationId)
            ->whereNull('consumed_at')->whereNull('released_at')->update(['consumed_at' => now(), 'updated_at' => now()]);
    }

    public function release(int $reservationId): void
    {
        DB::table('notification_case_consultation_reservations')->where('id', $reservationId)
            ->whereNull('consumed_at')->whereNull('released_at')->update(['released_at' => now(), 'updated_at' => now()]);
    }

    private function pendingCount(int $userId): int
    {
        return DB::table('notification_cases')->where('user_id', $userId)
            ->whereIn('status', array_map(fn ($s) => $s->value, array_filter(NotificationCaseStatus::cases(), fn ($s) => $s->isPending())))
            ->count();
    }
}
