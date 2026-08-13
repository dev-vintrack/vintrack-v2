<?php

use App\Application\NotificationCases\Services\NotificationCaseDocumentService;
use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script, $database, $caseId, $userId, $requestKey, $barrier, $output] = $argv + array_fill(0, 7, null);
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
$temp = tempnam(sys_get_temp_dir(), 's03-doc-');
file_put_contents($temp, "%PDF-1.4\n%%EOF");
try {
    $file = new UploadedFile($temp, $requestKey.'.pdf', 'application/pdf', null, true);
    $document = $app->make(NotificationCaseDocumentService::class)->upload(NotificationCase::findOrFail((int) $caseId), User::findOrFail((int) $userId), $file, $requestKey, $requestKey);
    $result = ['result' => 'OK', 'document_id' => $document->id, 'storage_key' => $document->storage_key];
} catch (Throwable $exception) {
    $result = ['result' => 'ERROR', 'type' => $exception::class, 'message' => $exception->getMessage()];
} finally {
    @unlink($temp);
}
file_put_contents($output, json_encode($result, JSON_THROW_ON_ERROR));
