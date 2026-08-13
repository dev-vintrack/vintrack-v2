<?php

use App\Application\NotificationCases\Exceptions\ConsultationBlockedException;
use App\Application\NotificationCases\Services\ConsultationAdmissionService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $userId, $requestKey, $barrier, $output] = $argv + array_fill(0, 6, null);
if ($database !== 'vintrack_sprint02_test') {
    throw new RuntimeException('Concurrency worker only accepts the verified disposable database.');
}
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE='.$database);
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = $database;

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$deadline = microtime(true) + 10;
while (! is_file($barrier) && microtime(true) < $deadline) {
    usleep(1000);
}

try {
    $reservationId = $app->make(ConsultationAdmissionService::class)->reserve((int) $userId, $requestKey);
    $result = ['result' => 'RESERVED', 'reservation_id' => $reservationId];
} catch (ConsultationBlockedException $exception) {
    $result = ['result' => 'BLOCKED', 'pending_count' => $exception->pendingCount];
} catch (Throwable $exception) {
    $result = ['result' => 'ERROR', 'type' => $exception::class, 'message' => $exception->getMessage()];
}

file_put_contents($output, json_encode($result, JSON_THROW_ON_ERROR));
