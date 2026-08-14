<?php

namespace App\Console\Commands;

use App\Application\NotificationCases\Services\NotificationDeadlineReminderService;
use Illuminate\Console\Command;

final class QueueNotificationDeadlineReminders extends Command
{
    protected $signature = 'notifications:queue-deadline-reminders {--limit=100}';

    protected $description = 'Encola recordatorios acotados de plazos de expedientes';

    public function handle(NotificationDeadlineReminderService $service): int
    {
        $stats = $service->queueDue((int) $this->option('limit'));
        $this->line(json_encode($stats, JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
