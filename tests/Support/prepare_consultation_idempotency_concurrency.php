<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$database = $argv[1] ?? '';
$mode = $argv[2] ?? 'same';
$output = $argv[3] ?? '';
if ($database !== 'vintrack_sprint08_idempotency' || ! in_array($mode, ['same', 'different'], true)) {
    throw new RuntimeException('Unsafe harness arguments.');
}

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE='.$database);
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = $database;
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$suffix = bin2hex(random_bytes(6));
DB::statement('CREATE TABLE IF NOT EXISTS sprint08_provider_calls (operation_key VARCHAR(128) PRIMARY KEY, calls INT NOT NULL DEFAULT 0, updated_at DATETIME(6) NOT NULL) ENGINE=InnoDB');

$data = DB::transaction(function () use ($suffix, $mode) {
    $providerId = DB::table('providers')->insertGetId([
        'code' => 'S08'.strtoupper($suffix), 'adapter_code' => 's08_fake_'.$suffix,
        'name' => 'SPRINT-08 Fake', 'base_url' => 'https://example.invalid',
        'policies_json' => json_encode([]), 'enabled' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $serviceCode = 's08_'.$suffix;
    $serviceId = DB::table('provider_services')->insertGetId([
        'provider_id' => $providerId, 'key' => strtoupper($serviceCode), 'service_code' => $serviceCode,
        'name' => 'SPRINT-08 Fake', 'credit_cost' => 1, 'available_credits' => 100,
        'enabled' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $userId = DB::table('users')->insertGetId([
        'name' => 'SPRINT08 IDEMPOTENCY', 'email' => $suffix.'@example.test',
        'password' => password_hash('test-only', PASSWORD_BCRYPT), 'activo' => 1,
        'status' => 'active', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('user_provider_wallets')->insert([
        'user_id' => $userId, 'provider_id' => $providerId, 'provider_service_id' => $serviceId,
        'balance' => 10, 'status' => 'active', 'validity_start' => now()->subDay(),
        'validity_end' => now()->addMonth(), 'created_at' => now(), 'updated_at' => now(),
    ]);

    return [
        'suffix' => $suffix, 'user_id' => $userId, 'provider_id' => $providerId,
        'provider_service_id' => $serviceId, 'service_code' => $serviceCode,
        'adapter_code' => 's08_fake_'.$suffix, 'value' => '1HGCM82633A123456',
        'keys' => $mode === 'same' ? ['s08-'.$suffix, 's08-'.$suffix] : ['s08-a-'.$suffix, 's08-b-'.$suffix],
    ];
});

$json = json_encode($data, JSON_THROW_ON_ERROR);
if ($output !== '') {
    file_put_contents($output, $json, LOCK_EX);
}
echo $json;
