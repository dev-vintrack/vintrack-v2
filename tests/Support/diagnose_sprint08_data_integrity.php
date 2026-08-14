<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$database = $argv[1] ?? '';
if (! in_array($database, ['vintrack_dev', 'vintrack_sprint08_idempotency', 'vintrack_sprint08_performance', 'vintrack_sprint08_backup_restore'], true)) {
    throw new RuntimeException('Unsafe integrity diagnostic database.');
}
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE='.$database);
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = $database;
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$scalar = static fn (string $sql, array $bindings = []): int => (int) (array_values((array) DB::selectOne($sql, $bindings))[0] ?? 0);
$pending = "'PENDING','SUBMITTED','UNDER_REVIEW','REJECTED'";
$results = [
    'duplicate_case_numbers' => $scalar('SELECT COUNT(*) FROM (SELECT case_number FROM notification_cases GROUP BY case_number HAVING COUNT(*) > 1) x'),
    'duplicate_operation_keys' => $scalar('SELECT COUNT(*) FROM (SELECT user_id, idempotency_key_hash FROM consultation_operations GROUP BY user_id, idempotency_key_hash HAVING COUNT(*) > 1) x'),
    'incompatible_operations' => $scalar("SELECT COUNT(*) FROM consultation_operations WHERE status NOT IN ('IN_PROGRESS','COMPLETED','FAILED_RETRYABLE','FAILED_AMBIGUOUS') OR (status = 'COMPLETED' AND (consultation_id IS NULL OR response_snapshot IS NULL))"),
    'orphan_evidence' => $scalar('SELECT COUNT(*) FROM notification_case_documents d LEFT JOIN notification_cases c ON c.id=d.notification_case_id LEFT JOIN users u ON u.id=d.uploaded_by_user_id WHERE c.id IS NULL OR u.id IS NULL'),
    'orphan_events' => $scalar('SELECT COUNT(*) FROM notification_case_events e LEFT JOIN notification_cases c ON c.id=e.notification_case_id WHERE e.notification_case_id IS NOT NULL AND c.id IS NULL'),
    'orphan_outbox' => $scalar('SELECT COUNT(*) FROM notification_outbox o LEFT JOIN notification_cases c ON c.id=o.case_id LEFT JOIN users u ON u.id=o.recipient_user_id WHERE (o.case_id IS NOT NULL AND c.id IS NULL) OR u.id IS NULL'),
    'orphan_deliveries' => $scalar("SELECT COUNT(*) FROM notification_deliveries d LEFT JOIN users u ON u.id=d.user_id LEFT JOIN notification_cases c ON c.id=d.related_id AND d.related_type='notification_case' WHERE (d.user_id IS NOT NULL AND u.id IS NULL) OR (d.related_type='notification_case' AND d.related_id IS NOT NULL AND c.id IS NULL)"),
    'impossible_case_statuses' => $scalar("SELECT COUNT(*) FROM notification_cases WHERE status NOT IN ('PENDING','SUBMITTED','UNDER_REVIEW','REJECTED','VALIDATED','CLOSED_NO_FOLLOW_UP')"),
    'invalid_deadlines' => $scalar("SELECT COUNT(*) FROM notification_cases nc JOIN consultations c ON c.id=nc.consultation_id WHERE nc.notification_deadline_at < c.created_at OR TIME(nc.notification_deadline_at) <> '23:59:59'"),
    'open_cases_past_auto_close' => $scalar("SELECT COUNT(*) FROM notification_cases WHERE status IN ({$pending}) AND auto_close_at <= NOW(6)"),
    'duplicate_active_vin_relationships' => $scalar("SELECT COUNT(*) FROM (SELECT vin_key FROM notification_cases WHERE vin_key IS NOT NULL AND status IN ({$pending}) GROUP BY vin_key HAVING COUNT(*) > 1) x"),
    'duplicate_consultation_case_links' => $scalar('SELECT COUNT(*) FROM (SELECT consultation_id FROM notification_cases GROUP BY consultation_id HAVING COUNT(*) > 1) x'),
];

echo json_encode(['database' => $database, 'diagnostics' => $results, 'all_zero' => array_sum($results) === 0], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
