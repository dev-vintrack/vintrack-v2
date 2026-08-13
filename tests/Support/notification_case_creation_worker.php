<?php

use App\Application\NotificationCases\Services\NotificationCaseCreationService;
use App\Domain\Consultas\Repositories\ConsultationRepositoryInterface;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script,$database,$consultationId,$reservationId,$barrier,$output] = $argv + array_fill(0, 6, null);
if ($database !== 'vintrack_sprint02_test') {
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
    $consultation = $app->make(ConsultationRepositoryInterface::class)->findById((int) $consultationId);
    $case = $app->make(NotificationCaseCreationService::class)->createOrReuse($consultation, 'harness', (int) $reservationId);
    $result = ['result' => 'OK', 'case_id' => $case?->id, 'case_number' => $case?->case_number];
} catch (Throwable $e) {
    $result = ['result' => 'ERROR', 'type' => $e::class, 'message' => $e->getMessage()];
}
file_put_contents($output, json_encode($result, JSON_THROW_ON_ERROR));
