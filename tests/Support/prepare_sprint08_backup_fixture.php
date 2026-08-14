<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script, $database, $evidenceRoot, $manifestPath] = $argv + array_fill(0, 4, null);
if ($database !== 'vintrack_sprint08_backup_source' || ! is_dir($evidenceRoot) || $manifestPath === null) {
    throw new RuntimeException('Unsafe backup fixture arguments.');
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
$now = now();

$fixture = DB::transaction(function () use ($suffix, $now, $evidenceRoot) {
    $providerId = DB::table('providers')->insertGetId([
        'code' => 'B'.strtoupper($suffix), 'adapter_code' => 'backup_'.$suffix, 'name' => 'BACKUP FIXTURE',
        'base_url' => 'https://example.invalid', 'policies_json' => json_encode([]), 'enabled' => 1,
        'created_at' => $now, 'updated_at' => $now,
    ]);
    $serviceId = DB::table('provider_services')->insertGetId([
        'provider_id' => $providerId, 'key' => 'BACKUP_'.$suffix, 'service_code' => 'backup_'.$suffix,
        'name' => 'BACKUP FIXTURE', 'credit_cost' => 0, 'available_credits' => 0, 'enabled' => 1,
        'created_at' => $now, 'updated_at' => $now,
    ]);
    $userId = DB::table('users')->insertGetId([
        'name' => 'BACKUP FIXTURE', 'email' => $suffix.'@example.test', 'password' => password_hash('test-only', PASSWORD_BCRYPT),
        'activo' => 1, 'status' => 'active', 'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now,
    ]);
    $consultationId = DB::table('consultations')->insertGetId([
        'user_id' => $userId, 'provider_id' => $providerId, 'provider_service_id' => $serviceId,
        'criterio' => 'vin', 'valor' => '1HGCM82633A123456', 'normalized_value' => '1HGCM82633A123456',
        'services' => json_encode(['backup_'.$suffix]), 'costo_credito' => 0, 'success' => 1, 'alerta_robo' => 1,
        'repuve_robo' => 1, 'created_at' => $now, 'updated_at' => $now,
    ]);
    $caseId = DB::table('notification_cases')->insertGetId([
        'consultation_id' => $consultationId, 'user_id' => $userId, 'case_number' => 'NT-2026-990001',
        'vin' => '1HGCM82633A123456', 'vin_key' => '1HGCM82633A123456', 'status' => 'SUBMITTED',
        'notification_deadline_at' => now()->addDays(3)->endOfDay(), 'opened_at' => $now,
        'auto_close_at' => now()->addDays(30), 'submitted_at' => $now, 'last_submitted_at' => $now,
        'lock_version' => 1, 'creation_key' => 'backup-fixture:'.$suffix, 'created_at' => $now, 'updated_at' => $now,
    ]);
    $eventId = DB::table('notification_case_events')->insertGetId([
        'notification_case_id' => $caseId, 'event_key' => 'backup-event:'.$suffix,
        'event_type' => 'CASE_SUBMITTED', 'actor_user_id' => $userId, 'actor_type' => 'USER',
        'occurred_at' => $now, 'correlation_id' => 'backup-fixture', 'created_at' => $now,
    ]);
    $outboxId = DB::table('notification_outbox')->insertGetId([
        'case_id' => $caseId, 'recipient_user_id' => $userId, 'event_type' => 'CASE_SUBMITTED',
        'channel' => 'PORTAL', 'dedup_key' => 'backup-outbox:'.$suffix, 'payload' => json_encode(['fixture' => true]),
        'available_at' => $now, 'status' => 'DELIVERED', 'attempts' => 1, 'sent_at' => $now,
        'created_at' => $now, 'updated_at' => $now,
    ]);
    $portalId = DB::table('portal_notifications')->insertGetId([
        'outbox_id' => $outboxId, 'recipient_user_id' => $userId, 'case_id' => $caseId,
        'type' => 'CASE_SUBMITTED', 'title' => 'BACKUP FIXTURE', 'body' => 'BACKUP FIXTURE',
        'action_path' => '/customer/notification-cases/'.$caseId, 'created_at' => $now,
    ]);
    DB::table('notification_deliveries')->insert([
        'uuid' => '00000000-0000-4000-8000-'.str_pad((string) $caseId, 12, '0', STR_PAD_LEFT),
        'user_id' => $userId, 'event_type' => 'CASE_SUBMITTED', 'related_type' => 'notification_case',
        'related_id' => $caseId, 'recipient' => $suffix.'@example.test', 'subject' => 'BACKUP FIXTURE',
        'status' => 'skipped', 'attempts' => 0, 'dedup_key' => 'backup-delivery:'.$suffix,
        'metadata' => json_encode(['fixture' => true]), 'created_at' => $now, 'updated_at' => $now,
    ]);

    $content = "%PDF-1.4\nSPRINT-08 SYNTHETIC EVIDENCE\n%%EOF\n";
    $storageKey = 'cases/'.$caseId.'/backup-fixture.pdf';
    $absolute = $evidenceRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $storageKey);
    if (! is_dir(dirname($absolute))) {
        mkdir(dirname($absolute), 0770, true);
    }
    file_put_contents($absolute, $content, LOCK_EX);
    $hash = hash_file('sha256', $absolute);
    $documentId = DB::table('notification_case_documents')->insertGetId([
        'notification_case_id' => $caseId, 'uploaded_by_user_id' => $userId,
        'original_name' => 'backup-fixture.pdf', 'storage_disk' => 'sprint08_fixture', 'storage_key' => $storageKey,
        'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size_bytes' => filesize($absolute),
        'sha256' => $hash, 'created_at' => $now, 'updated_at' => $now,
    ]);

    return compact('userId', 'consultationId', 'caseId', 'eventId', 'outboxId', 'portalId', 'documentId', 'storageKey', 'hash');
});

file_put_contents($manifestPath, json_encode($fixture, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX);
echo json_encode($fixture, JSON_THROW_ON_ERROR);
