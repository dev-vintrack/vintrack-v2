<?php

namespace App\Domain\NotificationCases\Services;

use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use DomainException;

final class NotificationCaseStateMachine
{
    private const TRANSITIONS = [
        'PENDING' => ['SUBMITTED', 'CLOSED_NO_FOLLOW_UP'],
        'SUBMITTED' => ['UNDER_REVIEW', 'REJECTED', 'CLOSED_NO_FOLLOW_UP'],
        'UNDER_REVIEW' => ['REJECTED', 'VALIDATED', 'CLOSED_NO_FOLLOW_UP'],
        'REJECTED' => ['SUBMITTED', 'CLOSED_NO_FOLLOW_UP'],
        'VALIDATED' => [],
        'CLOSED_NO_FOLLOW_UP' => [],
    ];

    public function canTransition(NotificationCaseStatus $from, NotificationCaseStatus $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$from->value], true);
    }

    public function assertTransition(NotificationCaseStatus $from, NotificationCaseStatus $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new DomainException("Transición no permitida: {$from->value} → {$to->value}.");
        }
    }
}
