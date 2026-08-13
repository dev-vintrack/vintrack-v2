<?php

namespace App\Application\NotificationCases\Services;

use App\Infrastructure\Persistence\Models\GlobalConfiguration;

final class NotificationCaseSettings
{
    private ?GlobalConfiguration $settings = null;

    private function model(): GlobalConfiguration
    {
        return $this->settings ??= GlobalConfiguration::settings();
    }

    public function deadlineDays(): int
    {
        return $this->model()->notification_case_deadline_days ?? 3;
    }

    public function maxOpenDays(): int
    {
        return $this->model()->notification_case_max_open_days ?? 30;
    }

    public function reuseDays(): int
    {
        return $this->model()->notification_case_reuse_days ?? 90;
    }

    public function maxPending(): int
    {
        return $this->model()->notification_case_max_pending ?? 3;
    }

    public function maxFiles(): int
    {
        return $this->model()->notification_case_max_files ?? 8;
    }

    public function maxFileBytes(): int
    {
        return $this->model()->notification_case_max_file_bytes ?? 3145728;
    }

    public function timezone(): string
    {
        return $this->model()->notification_case_timezone ?: 'America/Mexico_City';
    }

    public function reservationTtlSeconds(): int
    {
        return $this->model()->notification_case_reservation_ttl_seconds ?? 150;
    }
}
