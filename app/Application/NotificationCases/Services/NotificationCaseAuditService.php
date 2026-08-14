<?php

namespace App\Application\NotificationCases\Services;

use App\Infrastructure\Persistence\Models\NotificationCaseEvent;
use DomainException;

class NotificationCaseAuditService
{
    private const PRE_CASE_EVENTS = ['CONSULTATION_BLOCKED'];

    /** @param array<string, mixed> $metadata */
    public function record(
        ?int $caseId,
        string $eventType,
        string $eventKey,
        string $correlationId,
        ?int $actorUserId,
        string $actorType,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $reason = null,
        array $metadata = [],
        ?string $requestKey = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $fieldName = null,
        ?string $oldValue = null,
        ?string $newValue = null,
    ): NotificationCaseEvent {
        if ($caseId === null && ! in_array($eventType, self::PRE_CASE_EVENTS, true)) {
            throw new DomainException("El evento {$eventType} requiere expediente.");
        }

        $safeMetadata = array_intersect_key($metadata, array_flip([
            'pending_count', 'reservation_id', 'consultation_id', 'case_number',
            'deadline_at', 'source', 'conflicting_case_id', 'fields', 'lock_version',
            'document_id', 'mime_type', 'size_bytes', 'sha256', 'extension',
        ]));

        return NotificationCaseEvent::firstOrCreate(['event_key' => $eventKey], [
            'notification_case_id' => $caseId,
            'event_type' => $eventType,
            'actor_user_id' => $actorUserId,
            'actor_type' => $actorType,
            'occurred_at' => now(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
            'correlation_id' => $correlationId,
            'request_key' => $requestKey,
            'metadata' => $safeMetadata,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'created_at' => now(),
        ]);
    }
}
