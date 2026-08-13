<?php

namespace App\Domain\NotificationCases\Services;

use Carbon\CarbonImmutable;

final class NotificationCaseTemporalPolicy
{
    public function __construct(
        private readonly string $timezone,
        private readonly int $deadlineDays,
        private readonly int $maxOpenDays,
        private readonly int $reuseDays,
    ) {}

    public function deadline(CarbonImmutable $consultedAt): CarbonImmutable
    {
        return $consultedAt->setTimezone($this->timezone)->addDays($this->deadlineDays)->endOfDay()->setMicrosecond(0);
    }

    public function autoCloseAt(CarbonImmutable $openedAt): CarbonImmutable
    {
        return $openedAt->setTimezone($this->timezone)->addDays($this->maxOpenDays);
    }

    public function deadlineIsCurrent(CarbonImmutable $deadline, CarbonImmutable $now): bool
    {
        return $now->setTimezone($this->timezone)->setMicrosecond(0)->lessThanOrEqualTo($deadline->setMicrosecond(0));
    }

    public function validationIsReusable(CarbonImmutable $validatedAt, CarbonImmutable $consultedAt): bool
    {
        return $consultedAt->setTimezone($this->timezone)->lessThanOrEqualTo(
            $validatedAt->setTimezone($this->timezone)->addDays($this->reuseDays)
        );
    }
}
