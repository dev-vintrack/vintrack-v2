<?php

use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$database = $argv[1] ?? '';
if ($database !== 'vintrack_sprint02_test') {
    throw new RuntimeException('Concurrency harness only accepts the verified disposable database.');
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
$folioBase = random_int(100000, 899998);
$ids = DB::transaction(function () use ($suffix, $folioBase) {
    $userId = DB::table('users')->insertGetId([
        'name' => 'Concurrency User', 'email' => "sprint02-{$suffix}@example.test", 'password' => password_hash('test-only', PASSWORD_BCRYPT),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $providerId = DB::table('providers')->insertGetId([
        'code' => 'S02'.strtoupper($suffix), 'adapter_code' => 's02_'.$suffix, 'name' => 'Sprint02 Test Provider',
        'base_url' => 'https://example.test', 'policies_json' => json_encode([]), 'enabled' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $serviceId = DB::table('provider_services')->insertGetId([
        'provider_id' => $providerId, 'key' => 'S02_'.$suffix, 'service_code' => 's02_'.$suffix,
        'name' => 'Sprint02 Service', 'credit_cost' => 0, 'available_credits' => 0, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    for ($i = 0; $i < 2; $i++) {
        $consultationId = DB::table('consultations')->insertGetId([
            'user_id' => $userId, 'provider_id' => $providerId, 'provider_service_id' => $serviceId,
            'criterio' => 'vin', 'valor' => '1HGCM82633A12345'.$i, 'services' => json_encode(['s02_'.$suffix]),
            'costo_credito' => 0, 'success' => 1, 'alerta_robo' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('notification_cases')->insert([
            'consultation_id' => $consultationId, 'user_id' => $userId, 'case_number' => sprintf('NT-2026-%06d', $folioBase + $i),
            'vin' => '1HGCM82633A12345'.$i, 'vin_key' => '1HGCM82633A12345'.$i, 'status' => NotificationCaseStatus::PENDING->value,
            'notification_deadline_at' => now()->addDays(3)->endOfDay(), 'opened_at' => now(), 'auto_close_at' => now()->addDays(30),
            'lock_version' => 0, 'creation_key' => 'harness:'.$consultationId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    return ['user_id' => $userId, 'suffix' => $suffix];
});

echo json_encode($ids, JSON_THROW_ON_ERROR);
