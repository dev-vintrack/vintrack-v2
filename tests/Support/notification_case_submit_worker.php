<?php

use App\Application\NotificationCases\Services\NotificationCaseLifecycleService;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script,$database,$caseId,$userId,$requestKey,$barrier,$output] = $argv + array_fill(0, 7, null);
if (! in_array($database, ['vintrack_sprint02_test', 'vintrack_dev'], true)) {
    throw new RuntimeException('Unsafe database.');
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
    $case = $app->make(NotificationCaseLifecycleService::class)->submit(User::findOrFail((int) $userId), (int) $caseId, 0, $requestKey);
    $result = ['result' => 'OK', 'status' => $case->status->value, 'lock_version' => $case->lock_version];
} catch (Throwable $exception) {
    $result = ['result' => 'ERROR', 'type' => $exception::class, 'message' => $exception->getMessage()];
}
file_put_contents($output, json_encode($result,JSON_THROW_ON_ERROR));
