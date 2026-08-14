<?php

use App\Application\NotificationCases\Services\NotificationOutboxProcessor;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$worker = $argv[1] ?? '';
if (! preg_match('/^[a-zA-Z0-9-]{3,64}$/', $worker)) {
    throw new RuntimeException('Invalid worker.');
}

echo json_encode(app(NotificationOutboxProcessor::class)->process(1, 10, $worker));
