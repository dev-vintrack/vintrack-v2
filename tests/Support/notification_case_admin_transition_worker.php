<?php

use App\Application\NotificationCases\Services\NotificationCaseLifecycleService;
use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script,$database,$caseId,$analystId,$operation,$barrier,$output] = $argv + array_fill(0, 7, null);
if (! in_array($database, ['vintrack_sprint02_test', 'vintrack_dev'], true) || ! in_array($operation, ['validate', 'reject', 'auto-close', 'assign-a', 'assign-b'], true)) {
    throw new RuntimeException('Unsafe parameters.');
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
    $service = $app->make(NotificationCaseLifecycleService::class);
    if (str_starts_with($operation, 'assign-')) {
        $vin = $operation === 'assign-a' ? '1HGCM82633A123456' : '1HGCM82633A654321';
        $case = $service->assignVinOnce(User::findOrFail((int) $analystId), (int) $caseId, $vin, 's05-'.$operation);
        $result = ['result' => 'OK', 'status' => $case->status->value, 'vin' => $case->vin, 'lock_version' => $case->lock_version];
    } elseif ($operation === 'auto-close') {
        $closed = $service->autoCloseDue(1, (int) $caseId, 0);
        $result = ['result' => 'OK', 'closed' => $closed];
    } else {
        $to = $operation === 'validate' ? NotificationCaseStatus::VALIDATED : NotificationCaseStatus::REJECTED;
        $case = $service->transition(User::findOrFail((int) $analystId), (int) $caseId, $to, 0, 's05-'.$operation.'-'.bin2hex(random_bytes(3)), $operation === 'reject' ? 'MOTIVO CONCURRENTE' : null);
        $result = ['result' => 'OK', 'status' => $case->status->value, 'lock_version' => $case->lock_version];
    }
} catch (Throwable $e) {
    $result = ['result' => 'ERROR', 'type' => $e::class, 'message' => $e->getMessage()];
}
file_put_contents($output, json_encode($result,JSON_THROW_ON_ERROR));
