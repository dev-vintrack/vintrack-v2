<?php

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
$result = DB::transaction(function () use ($suffix) {
    $roleId = DB::table('roles')->where('nombre', 'oficial')->value('id_rol');
    if (! $roleId) {
        throw new RuntimeException('Required oficial role is missing.');
    }
    $userId = DB::table('users')->insertGetId(['name' => 'Sprint04 Double Submit', 'email' => "s04-{$suffix}@example.test", 'password' => password_hash('test-only', PASSWORD_BCRYPT), 'id_rol' => $roleId, 'activo' => 1, 'status' => 'active', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    $providerId = DB::table('providers')->insertGetId(['code' => 'S04'.strtoupper($suffix), 'adapter_code' => 's04_'.$suffix, 'name' => 'Sprint04 Provider', 'base_url' => 'https://example.test', 'policies_json' => json_encode([]), 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $serviceId = DB::table('provider_services')->insertGetId(['provider_id' => $providerId, 'key' => 'S04_'.$suffix, 'service_code' => 's04_'.$suffix, 'name' => 'Sprint04 Service', 'credit_cost' => 0, 'available_credits' => 0, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $consultationId = DB::table('consultations')->insertGetId(['user_id' => $userId, 'provider_id' => $providerId, 'provider_service_id' => $serviceId, 'criterio' => 'vin', 'valor' => '1HGCM82633A123456', 'services' => json_encode(['s04_'.$suffix]), 'costo_credito' => 0, 'success' => 1, 'alerta_robo' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $caseId = DB::table('notification_cases')->insertGetId(['consultation_id' => $consultationId, 'user_id' => $userId, 'case_number' => 'NT-2026-8'.random_int(10000, 99999), 'vin' => '1HGCM82633A123456', 'vin_key' => '1HGCM82633A123456', 'recovery_place' => 'LUGAR', 'country' => 'MEXICO', 'state' => 'PUEBLA', 'municipality' => 'PUEBLA', 'recovered_at' => now()->subHour(), 'license_plate' => 'ABC123', 'make' => 'HONDA', 'model_year' => 2020, 'origin' => 'NACIONAL', 'authority' => 'FISCALIA', 'iph' => 'IPH-1', 'investigation_file' => 'CI-1', 'safekeeping' => 'CORRALON', 'status' => 'PENDING', 'notification_deadline_at' => now()->addDays(3)->endOfDay(), 'opened_at' => now(), 'auto_close_at' => now()->addDays(30), 'lock_version' => 0, 'creation_key' => 's04:'.$suffix, 'created_at' => now(), 'updated_at' => now()]);

    return compact('userId', 'caseId', 'consultationId', 'providerId', 'serviceId');
});
echo json_encode(['user_id' => $result['userId'], 'case_id' => $result['caseId'], 'consultation_id' => $result['consultationId'], 'provider_id' => $result['providerId'], 'service_id' => $result['serviceId']], JSON_THROW_ON_ERROR);
