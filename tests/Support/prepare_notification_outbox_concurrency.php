<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$suffix = $argv[1] ?? '';
if (! preg_match('/^[a-zA-Z0-9-]{8,64}$/', $suffix)) {
    throw new RuntimeException('Invalid suffix.');
}
$userId = DB::table('users')->orderBy('id')->value('id');
if (! $userId) {
    throw new RuntimeException('A local test user is required.');
}
$id = DB::table('notification_outbox')->insertGetId([
    'case_id' => null,
    'recipient_user_id' => $userId,
    'event_type' => 'CASE_CREATED',
    'channel' => 'PORTAL',
    'dedup_key' => 's07-concurrency:'.$suffix,
    'payload' => json_encode(['message' => 'CONCURRENCY TEST']),
    'available_at' => now(),
    'status' => 'PENDING',
    'attempts' => 0,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo json_encode(['outbox_id' => $id, 'dedup_key' => 's07-concurrency:'.$suffix]);
