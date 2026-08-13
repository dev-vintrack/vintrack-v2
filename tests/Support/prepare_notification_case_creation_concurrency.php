<?php

use App\Application\NotificationCases\Services\ConsultationAdmissionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$database = $argv[1] ?? '';
$mode = $argv[2] ?? 'same';
if ($database !== 'vintrack_sprint02_test' || ! in_array($mode, ['same', 'distinct'], true)) {
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
$suffix = bin2hex(random_bytes(5));

$data = DB::transaction(function () use ($suffix, $mode) {
    $providerId = DB::table('providers')->insertGetId([
        'code' => 'C'.strtoupper($suffix), 'adapter_code' => 'c_'.$suffix, 'name' => 'Concurrency Creation', 'base_url' => 'https://example.test',
        'policies_json' => json_encode([]), 'enabled' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $serviceId = DB::table('provider_services')->insertGetId([
        'provider_id' => $providerId, 'key' => 'C_'.$suffix, 'service_code' => 'c_'.$suffix, 'name' => 'Concurrency Creation',
        'credit_cost' => 0, 'available_credits' => 0, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $items = [];
    $count = $mode === 'same' ? 1 : 2;
    for ($i = 0; $i < $count; $i++) {
        $userId = DB::table('users')->insertGetId(['name' => 'Creation User', 'email' => "create-{$suffix}-{$i}@example.test", 'password' => password_hash('test-only', PASSWORD_BCRYPT), 'created_at' => now(), 'updated_at' => now()]);
        $consultationId = DB::table('consultations')->insertGetId([
            'user_id' => $userId, 'provider_id' => $providerId, 'provider_service_id' => $serviceId, 'criterio' => 'vin', 'valor' => sprintf('1HGCM82633A%06d', random_int(0, 999999)),
            'services' => json_encode(['c_'.$suffix]), 'costo_credito' => 0, 'success' => 1, 'alerta_robo' => 1, 'repuve_robo' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $items[] = ['user_id' => $userId, 'consultation_id' => $consultationId];
    }

    return $items;
});

$admission = $app->make(ConsultationAdmissionService::class);
if ($mode === 'same') {
    $item = $data[0];
    $data = [
        [...$item, 'reservation_id' => $admission->reserve($item['user_id'], 'create-a-'.$suffix)],
        [...$item, 'reservation_id' => $admission->reserve($item['user_id'], 'create-b-'.$suffix)],
    ];
} else {
    foreach ($data as $i => $item) {
        $data[$i]['reservation_id'] = $admission->reserve($item['user_id'], 'folio-'.$i.'-'.$suffix);
    }
}
echo json_encode(['suffix' => $suffix, 'items' => $data], JSON_THROW_ON_ERROR);
