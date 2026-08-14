<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script,$database,$caseId,$ownerId,$analystId,$consultationId,$providerId,$serviceId] = $argv + array_fill(0, 8, null);
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
$snapshot = DB::transaction(function () use ($caseId, $ownerId, $analystId, $consultationId, $providerId, $serviceId) {
    $case = DB::table('notification_cases')->where('id', (int) $caseId)->first();
    if (! $case || ! str_starts_with($case->creation_key, 's05:')) {
        throw new RuntimeException('Unsafe cleanup target.');
    }
    $events = DB::table('notification_case_events')->where('notification_case_id', (int) $caseId)->whereIn('event_type', ['CASE_VALIDATED', 'CASE_REJECTED', 'CASE_AUTO_CLOSED', 'CASE_VIN_ASSIGNED'])->get(['event_type'])->pluck('event_type')->all();
    $outbox = DB::table('notification_outbox')->where('case_id', (int) $caseId)->get(['event_type'])->pluck('event_type')->all();
    $result = ['status' => $case->status, 'vin' => $case->vin, 'lock_version' => $case->lock_version, 'events' => $events, 'outbox' => $outbox];
    DB::table('notification_outbox')->where('case_id', (int) $caseId)->delete();
    DB::table('notification_case_events')->where('notification_case_id', (int) $caseId)->delete();
    DB::table('notification_cases')->where('id', (int) $caseId)->delete();
    DB::table('consultations')->where('id', (int) $consultationId)->delete();
    DB::table('users')->whereIn('id', [(int) $ownerId, (int) $analystId])->delete();
    DB::table('provider_services')->where('id', (int) $serviceId)->delete();
    DB::table('providers')->where('id', (int) $providerId)->delete();

    return $result;
});
echo json_encode($snapshot + ['cleaned' => true],JSON_THROW_ON_ERROR);
