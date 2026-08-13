<?php

use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$database = $argv[1] ?? '';
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

$suffix = bin2hex(random_bytes(5));
$result = DB::transaction(function () use ($suffix): array {
    $userId = DB::table('users')->insertGetId(['name' => 'Sprint03 Concurrency', 'email' => "s03-{$suffix}@example.test", 'password' => password_hash('test-only', PASSWORD_BCRYPT), 'created_at' => now(), 'updated_at' => now()]);
    $providerId = DB::table('providers')->insertGetId(['code' => 'S03'.strtoupper($suffix), 'adapter_code' => 's03_'.$suffix, 'name' => 'Sprint03 Provider', 'base_url' => 'https://example.test', 'policies_json' => json_encode([]), 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $serviceId = DB::table('provider_services')->insertGetId(['provider_id' => $providerId, 'key' => 'S03_'.$suffix, 'service_code' => 's03_'.$suffix, 'name' => 'Sprint03 Service', 'credit_cost' => 0, 'available_credits' => 0, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $consultationId = DB::table('consultations')->insertGetId(['user_id' => $userId, 'provider_id' => $providerId, 'provider_service_id' => $serviceId, 'criterio' => 'vin', 'valor' => '1HGCM82633A123456', 'services' => json_encode(['s03_'.$suffix]), 'costo_credito' => 0, 'success' => 1, 'alerta_robo' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $caseId = DB::table('notification_cases')->insertGetId(['consultation_id' => $consultationId, 'user_id' => $userId, 'case_number' => 'NT-2026-9'.random_int(10000, 99999), 'vin' => '1HGCM82633A123456', 'vin_key' => '1HGCM82633A123456', 'status' => NotificationCaseStatus::PENDING->value, 'notification_deadline_at' => now()->addDays(3)->endOfDay(), 'opened_at' => now(), 'auto_close_at' => now()->addDays(30), 'lock_version' => 0, 'creation_key' => 's03:'.$suffix, 'created_at' => now(), 'updated_at' => now()]);
    for ($i = 0; $i < 7; $i++) {
        DB::table('notification_case_documents')->insert(['notification_case_id' => $caseId, 'uploaded_by_user_id' => $userId, 'original_name' => "existing-{$i}.pdf", 'storage_disk' => 'local', 'storage_key' => "s03-harness-existing/{$suffix}-{$i}.pdf", 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size_bytes' => 15, 'sha256' => hash('sha256', "existing-{$i}"), 'created_at' => now(), 'updated_at' => now()]);
    }

    return ['user_id' => $userId, 'case_id' => $caseId, 'provider_id' => $providerId, 'service_id' => $serviceId];
});
echo json_encode($result, JSON_THROW_ON_ERROR);
