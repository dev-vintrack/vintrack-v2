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
$outbox = DB::table('notification_outbox')->where('dedup_key', 's07-concurrency:'.$suffix)->first();
$portalCount = $outbox ? DB::table('portal_notifications')->where('outbox_id', $outbox->id)->count() : 0;
$status = $outbox?->status;
if ($outbox) {
    DB::table('portal_notifications')->where('outbox_id', $outbox->id)->delete();
    DB::table('notification_outbox')->where('id', $outbox->id)->delete();
}

echo json_encode(['status' => $status, 'portal_count' => $portalCount, 'cleaned' => (bool) $outbox]);
