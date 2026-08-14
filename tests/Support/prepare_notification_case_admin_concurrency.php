<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script, $database, $mode] = $argv + [null, null, 'normal'];
if (! in_array($database, ['vintrack_sprint02_test', 'vintrack_dev'], true) || ! in_array($mode, ['normal', 'due', 'plate'], true)) {
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
$suffix = bin2hex(random_bytes(5));
$result = DB::transaction(function () use ($suffix, $mode) {
    $ownerRole = DB::table('roles')->where('nombre', 'oficial')->value('id_rol');
    $analystRole = DB::table('roles')->where('nombre', 'analista')->value('id_rol');
    if (! $ownerRole || ! $analystRole) {
        throw new RuntimeException('Required roles missing.');
    }
    $ownerId = DB::table('users')->insertGetId(['name' => 'S05 Owner', 'email' => "s05-owner-{$suffix}@example.test", 'password' => password_hash('test', PASSWORD_BCRYPT), 'id_rol' => $ownerRole, 'activo' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    $analystId = DB::table('users')->insertGetId(['name' => 'S05 Analyst', 'email' => "s05-analyst-{$suffix}@example.test", 'password' => password_hash('test', PASSWORD_BCRYPT), 'id_rol' => $analystRole, 'activo' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    $providerId = DB::table('providers')->insertGetId(['code' => 'S05'.strtoupper($suffix), 'adapter_code' => 's05_'.$suffix, 'name' => 'S05 Provider', 'base_url' => 'https://example.test', 'policies_json' => json_encode([]), 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $serviceId = DB::table('provider_services')->insertGetId(['provider_id' => $providerId, 'key' => 'S05_'.$suffix, 'service_code' => 's05_'.$suffix, 'name' => 'S05 Service', 'credit_cost' => 0, 'available_credits' => 0, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $consultationId = DB::table('consultations')->insertGetId(['user_id' => $ownerId, 'provider_id' => $providerId, 'provider_service_id' => $serviceId, 'criterio' => $mode === 'plate' ? 'placa' : 'vin', 'valor' => $mode === 'plate' ? 'ABC123' : '1HGCM82633A123456', 'services' => json_encode(['s05']), 'costo_credito' => 0, 'success' => 1, 'alerta_robo' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $caseId = DB::table('notification_cases')->insertGetId(['consultation_id' => $consultationId, 'user_id' => $ownerId, 'case_number' => 'NT-2026-7'.random_int(10000, 99999), 'vin' => $mode === 'plate' ? null : '1HGCM82633A123456', 'vin_key' => $mode === 'plate' ? null : '1HGCM82633A123456', 'recovery_place' => 'LUGAR', 'country' => 'MEXICO', 'state' => 'PUEBLA', 'municipality' => 'PUEBLA', 'recovered_at' => now()->subHour(), 'license_plate' => 'ABC123', 'make' => 'HONDA', 'model_year' => 2020, 'origin' => 'NACIONAL', 'authority' => 'FISCALIA', 'iph' => 'IPH-1', 'investigation_file' => 'CI-1', 'safekeeping' => 'CORRALON', 'status' => $mode === 'plate' ? 'PENDING' : 'UNDER_REVIEW', 'notification_deadline_at' => now()->addDays(3)->endOfDay(), 'opened_at' => now()->subDays(31), 'auto_close_at' => $mode === 'due' ? now()->subSecond() : now()->addDay(), 'lock_version' => 0, 'creation_key' => 's05:'.$suffix, 'created_at' => now(), 'updated_at' => now()]);

    return compact('ownerId', 'analystId', 'providerId', 'serviceId', 'consultationId', 'caseId');
});
echo json_encode($result, JSON_THROW_ON_ERROR);
