<?php

namespace App\Application\NotificationCases\Services;

use App\Infrastructure\Persistence\Models\NotificationOutbox;

final class NotificationCaseOutboxService
{
    /** @param array<string, scalar|null> $payload */
    public function queue(?int $caseId, int $recipientUserId, string $eventType, string $channel, string $dedupKey, array $payload): NotificationOutbox
    {
        $safePayload = array_intersect_key($payload, array_flip(['case_number', 'deadline_at', 'status', 'message', 'pending_count']));

        return NotificationOutbox::firstOrCreate(['dedup_key' => $dedupKey], [
            'case_id' => $caseId,
            'recipient_user_id' => $recipientUserId,
            'event_type' => $eventType,
            'channel' => $channel,
            'payload' => $safePayload,
            'available_at' => now(),
            'status' => 'PENDING',
            'attempts' => 0,
        ]);
    }
}
