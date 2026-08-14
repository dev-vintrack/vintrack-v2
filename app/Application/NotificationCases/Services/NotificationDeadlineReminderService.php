<?php

namespace App\Application\NotificationCases\Services;

use App\Infrastructure\Persistence\Models\NotificationCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class NotificationDeadlineReminderService
{
    public function __construct(
        private readonly NotificationCaseOutboxService $outbox,
        private readonly NotificationCaseSettings $settings,
    ) {}

    /** @return array{examined:int,queued:int} */
    public function queueDue(int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        $now = CarbonImmutable::now($this->settings->timezone());
        $cases = NotificationCase::query()
            ->whereIn('status', ['PENDING', 'REJECTED'])
            ->where('notification_deadline_at', '<=', $now->addDay())
            ->orderBy('notification_deadline_at')->orderBy('id')->limit($limit)->get();
        $queued = 0;

        foreach ($cases as $case) {
            $eventType = $case->notification_deadline_at->lessThanOrEqualTo($now)
                ? 'DEADLINE_REACHED'
                : 'DEADLINE_REMINDER_T_MINUS_1_DAY';
            $dedup = 'case-deadline:'.$case->id.':'.$eventType.':'.$case->notification_deadline_at->format('YmdHis');
            $keys = [$dedup.':portal', $dedup.':email'];
            $before = DB::table('notification_outbox')->whereIn('dedup_key', $keys)->count();
            $this->outbox->queueChannels($case->id, $case->user_id, $eventType, $dedup, [
                'case_number' => $case->case_number,
                'deadline_at' => $case->notification_deadline_at->format('Y-m-d H:i:s'),
                'status' => $case->status->value,
            ]);
            $queued += DB::table('notification_outbox')->whereIn('dedup_key', $keys)->count() - $before;
        }

        return ['examined' => $cases->count(), 'queued' => $queued];
    }
}
