<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script, $database, $caseId, $userId, $providerId, $serviceId] = $argv + array_fill(0, 6, null);
if (! in_array($database, ['vintrack_sprint02_test', 'vintrack_dev'], true) || ! ctype_digit((string) $caseId) || ! ctype_digit((string) $userId) || ! ctype_digit((string) $providerId) || ! ctype_digit((string) $serviceId)) {
    throw new RuntimeException('Unsafe cleanup target.');
}
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE='.$database);
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = $database;
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$activeBeforeCleanup = DB::transaction(function () use ($caseId, $userId, $providerId, $serviceId): int {
    $case = DB::table('notification_cases')->where('id', (int) $caseId)->where('user_id', (int) $userId)->first();
    if ($case === null || ! str_starts_with((string) $case->creation_key, 's03:')) {
        throw new RuntimeException('Cleanup target is not an SPRINT-03 harness case.');
    }
    $active = DB::table('notification_case_documents')->where('notification_case_id', (int) $caseId)->whereNull('removed_at')->count();
    $keys = DB::table('notification_case_documents')->where('notification_case_id', (int) $caseId)->pluck('storage_key');
    foreach ($keys as $key) {
        if (str_starts_with((string) $key, 'notification-case-evidence/')) {
            Storage::disk('local')->delete((string) $key);
        }
    }
    DB::table('notification_case_events')->where('notification_case_id', (int) $caseId)->delete();
    DB::table('notification_case_documents')->where('notification_case_id', (int) $caseId)->delete();
    DB::table('notification_cases')->where('id', (int) $caseId)->delete();
    DB::table('consultations')->where('id', $case->consultation_id)->delete();
    DB::table('users')->where('id', (int) $userId)->delete();
    DB::table('provider_services')->where('id', (int) $serviceId)->where('provider_id', (int) $providerId)->delete();
    DB::table('providers')->where('id', (int) $providerId)->delete();

    return $active;
});
echo json_encode(['active_before_cleanup' => $activeBeforeCleanup, 'cleaned' => true], JSON_THROW_ON_ERROR).PHP_EOL;
