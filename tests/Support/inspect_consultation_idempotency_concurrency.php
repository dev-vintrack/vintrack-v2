<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$database = $argv[1] ?? '';
$payloadPath = $argv[2] ?? '';
if ($database !== 'vintrack_sprint08_idempotency' || ! is_file($payloadPath)) {
    throw new RuntimeException('Unsafe inspection arguments.');
}
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE='.$database);
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = $database;
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$payload = json_decode(file_get_contents($payloadPath), true, 512, JSON_THROW_ON_ERROR);

echo json_encode([
    'provider_calls' => (int) DB::table('sprint08_provider_calls')->where('operation_key', $payload['suffix'])->value('calls'),
    'debits' => DB::table('wallet_ledger as ledger')
        ->join('user_provider_wallets as wallet', 'wallet.id', '=', 'ledger.wallet_id')
        ->where('wallet.user_id', $payload['user_id'])
        ->where('ledger.correlation_id', 'like', 'consult-operation-%')->count(),
    'consultations' => DB::table('consultations')->where('user_id', $payload['user_id'])->count(),
    'operations' => DB::table('consultation_operations')->where('user_id', $payload['user_id'])->count(),
    'operation_statuses' => DB::table('consultation_operations')->where('user_id', $payload['user_id'])->orderBy('id')->pluck('status'),
], JSON_THROW_ON_ERROR);
