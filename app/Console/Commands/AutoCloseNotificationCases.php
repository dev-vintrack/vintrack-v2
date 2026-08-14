<?php

namespace App\Console\Commands;

use App\Application\NotificationCases\Services\NotificationCaseLifecycleService;
use Illuminate\Console\Command;

final class AutoCloseNotificationCases extends Command
{
    protected $signature = 'notifications:auto-close {--limit=100}';

    protected $description = 'Cierra un lote acotado de expedientes vencidos usando el lifecycle canónico';

    public function handle(NotificationCaseLifecycleService $service): int
    {
        $closed = $service->autoCloseDue(max(1, min((int) $this->option('limit'), 500)));
        $this->line(json_encode(['closed' => $closed], JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
