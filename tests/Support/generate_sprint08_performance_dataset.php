<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$database = $argv[1] ?? '';
$consultationCount = (int) ($argv[2] ?? 100000);
if ($database !== 'vintrack_sprint08_performance' || $consultationCount < 1000 || $consultationCount > 100000) {
    throw new RuntimeException('Unsafe performance dataset arguments.');
}
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE='.$database);
putenv('MAIL_MAILER=array');
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = $database;
$_ENV['MAIL_MAILER'] = 'array';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
DB::disableQueryLog();

$started = microtime(true);
DB::statement('SET FOREIGN_KEY_CHECKS=0');
foreach ([
    'portal_notifications', 'notification_deliveries', 'notification_outbox', 'notification_case_events',
    'notification_case_documents', 'notification_case_vin_reconciliations', 'notification_cases',
    'notification_case_consultation_reservations', 'consultation_operations', 'wallet_ledger',
    'user_provider_wallets', 'consultations', 'vehicles', 'provider_services', 'providers', 'users',
] as $table) {
    DB::statement("TRUNCATE TABLE `{$table}`");
}
DB::statement('SET FOREIGN_KEY_CHECKS=1');

$now = now();
$providerId = DB::table('providers')->insertGetId([
    'code' => 'S08PERF', 'adapter_code' => 's08_perf', 'name' => 'SPRINT-08 PERFORMANCE',
    'base_url' => 'https://example.invalid', 'policies_json' => json_encode([]), 'enabled' => 1,
    'created_at' => $now, 'updated_at' => $now,
]);
$serviceId = DB::table('provider_services')->insertGetId([
    'provider_id' => $providerId, 'key' => 'S08_PERF', 'service_code' => 's08_perf',
    'name' => 'SPRINT-08 PERFORMANCE', 'credit_cost' => 0, 'available_credits' => 0,
    'enabled' => 1, 'created_at' => $now, 'updated_at' => $now,
]);

$users = [];
for ($i = 1; $i <= 200; $i++) {
    $users[] = [
        'name' => sprintf('SYNTHETIC USER %03d', $i), 'email' => sprintf('synthetic-%03d@example.test', $i),
        'password' => password_hash('test-only', PASSWORD_BCRYPT), 'activo' => 1, 'status' => 'active',
        'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now,
    ];
}
DB::table('users')->insert($users);

$base = new DateTimeImmutable('2025-01-01 00:00:00');
for ($offset = 0; $offset < $consultationCount; $offset += 1000) {
    $rows = [];
    $limit = min(1000, $consultationCount - $offset);
    for ($j = 0; $j < $limit; $j++) {
        $n = $offset + $j + 1;
        $plate = $n % 5 === 0;
        $identity = $n % 5000;
        $value = $plate ? sprintf('S%06d', $identity) : sprintf('1HGCM%012d', $identity);
        $at = $base->modify('+'.($n % 600).' days')->modify('+'.($n % 86400).' seconds')->format('Y-m-d H:i:s');
        $rows[] = [
            'user_id' => (($n - 1) % 200) + 1, 'provider_id' => $providerId,
            'provider_service_id' => $serviceId, 'criterio' => $plate ? 'placa' : 'vin', 'valor' => $value,
            'normalized_value' => strtoupper(trim($value)),
            'services' => json_encode(['s08_perf']), 'costo_credito' => 0, 'success' => 1,
            'alerta_robo' => $n % 4 === 0 ? 1 : 0, 'repuve_robo' => $n % 4 === 0 ? 1 : 0,
            'created_at' => $at, 'updated_at' => $at,
        ];
    }
    DB::table('consultations')->insert($rows);
}

$statuses = ['PENDING', 'SUBMITTED', 'UNDER_REVIEW', 'REJECTED', 'VALIDATED', 'CLOSED_NO_FOLLOW_UP'];
$caseRows = [];
$eventRows = [];
for ($caseNumber = 1; $caseNumber <= intdiv($consultationCount, 10); $caseNumber++) {
    $consultationId = ($caseNumber * 10) - ($caseNumber % 2 === 1 ? 1 : 0);
    $status = $statuses[$caseNumber % count($statuses)];
    $plate = $consultationId % 5 === 0;
    $identity = $consultationId % 5000;
    $opened = $base->modify('+'.($consultationId % 600).' days')->modify('+'.($consultationId % 86400).' seconds');
    $validated = $status === 'VALIDATED' ? $opened->modify('+5 days')->format('Y-m-d H:i:s.u') : null;
    $closed = $status === 'CLOSED_NO_FOLLOW_UP' ? $opened->modify('+30 days')->format('Y-m-d H:i:s.u') : null;
    $caseRows[] = [
        'consultation_id' => $consultationId, 'user_id' => (($consultationId - 1) % 200) + 1,
        'case_number' => sprintf('NT-2026-%06d', $caseNumber),
        'vin' => $plate ? null : sprintf('1HGCM%012d', $identity),
        'vin_key' => $plate ? null : sprintf('1HGCM%012d', $identity),
        'license_plate' => $plate ? sprintf('S%06d', $identity) : sprintf('P%06d', $identity),
        'make' => 'MARCA '.($identity % 20), 'model' => 'MODELO '.($identity % 50), 'model_year' => 2000 + ($identity % 26),
        'status' => $status, 'notification_deadline_at' => $opened->modify('+3 days')->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
        'opened_at' => $opened->format('Y-m-d H:i:s.u'), 'auto_close_at' => $opened->modify('+30 days')->format('Y-m-d H:i:s.u'),
        'submitted_at' => in_array($status, ['SUBMITTED', 'UNDER_REVIEW', 'REJECTED', 'VALIDATED'], true) ? $opened->modify('+1 day')->format('Y-m-d H:i:s.u') : null,
        'validated_at' => $validated, 'closed_at' => $closed, 'lock_version' => 0,
        'creation_key' => 's08-performance:'.$consultationId,
        'created_at' => $opened->format('Y-m-d H:i:s.u'), 'updated_at' => $opened->format('Y-m-d H:i:s.u'),
    ];
    if (count($caseRows) === 500) {
        DB::table('notification_cases')->insert($caseRows);
        $caseRows = [];
    }
}
if ($caseRows !== []) {
    DB::table('notification_cases')->insert($caseRows);
}

$cases = DB::table('notification_cases')->select('id', 'submitted_at', 'created_at')->orderBy('id')->cursor();
$eventBatch = [];
foreach ($cases as $case) {
    $eventBatch[] = [
        'notification_case_id' => $case->id, 'event_key' => 's08-performance-event:'.$case->id,
        'event_type' => $case->submitted_at ? 'CASE_SUBMITTED' : 'CASE_CREATED', 'actor_type' => 'SYSTEM',
        'occurred_at' => $case->submitted_at ?: $case->created_at, 'correlation_id' => 's08-performance',
        'created_at' => $case->submitted_at ?: $case->created_at,
    ];
    if (count($eventBatch) === 1000) {
        DB::table('notification_case_events')->insert($eventBatch);
        $eventBatch = [];
    }
}
if ($eventBatch !== []) {
    DB::table('notification_case_events')->insert($eventBatch);
}

DB::statement('ANALYZE TABLE consultations, notification_cases, notification_case_events');
echo json_encode([
    'database' => $database, 'users' => DB::table('users')->count(),
    'consultations' => DB::table('consultations')->count(), 'cases' => DB::table('notification_cases')->count(),
    'events' => DB::table('notification_case_events')->count(),
    'seconds' => round(microtime(true) - $started, 3),
], JSON_THROW_ON_ERROR);
