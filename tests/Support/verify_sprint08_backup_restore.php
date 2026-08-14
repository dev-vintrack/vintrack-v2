<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script, $database, $evidenceRoot, $manifestPath] = $argv + array_fill(0, 4, null);
if ($database !== 'vintrack_sprint08_backup_restore' || ! is_dir($evidenceRoot) || ! is_file($manifestPath)) {
    throw new RuntimeException('Unsafe restore verification arguments.');
}
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE='.$database);
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = $database;
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$fixture = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
$file = $evidenceRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $fixture['storageKey']);

$checks = [
    'consultation' => DB::table('consultations')->where('id', $fixture['consultationId'])->exists(),
    'case' => DB::table('notification_cases')->where('id', $fixture['caseId'])->where('case_number', 'NT-2026-990001')->where('status', 'SUBMITTED')->exists(),
    'event' => DB::table('notification_case_events')->where('id', $fixture['eventId'])->exists(),
    'outbox' => DB::table('notification_outbox')->where('id', $fixture['outboxId'])->where('status', 'DELIVERED')->exists(),
    'portal' => DB::table('portal_notifications')->where('id', $fixture['portalId'])->where('recipient_user_id', $fixture['userId'])->exists(),
    'document' => DB::table('notification_case_documents')->where('id', $fixture['documentId'])->where('sha256', $fixture['hash'])->exists(),
    'physical_file' => is_file($file),
    'physical_hash' => is_file($file) && hash_equals($fixture['hash'], hash_file('sha256', $file)),
    'foreign_keys' => count(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME IN ('notification_cases','notification_case_events','notification_outbox','portal_notifications','notification_case_documents')", [$database])) >= 10,
    'owner_authorization_basis' => DB::table('notification_cases')->where('id', $fixture['caseId'])->where('user_id', $fixture['userId'])->exists(),
];
echo json_encode(['checks' => $checks, 'all_pass' => ! in_array(false, $checks, true)], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
