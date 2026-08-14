<?php

namespace App\Console\Commands;

use App\Application\NotificationCases\Services\NotificationOutboxProcessor;
use Illuminate\Console\Command;

final class ProcessNotificationOutbox extends Command
{
    protected $signature = 'notifications:process-outbox {--limit=100} {--max-seconds=50}';

    protected $description = 'Procesa un lote acotado del outbox de notificaciones';

    public function handle(NotificationOutboxProcessor $processor): int
    {
        $stats = $processor->process((int) $this->option('limit'), (int) $this->option('max-seconds'));
        $this->line(json_encode($stats, JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
