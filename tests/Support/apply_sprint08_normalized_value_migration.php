<?php

use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$database = $argv[1] ?? '';
$action = $argv[2] ?? 'up';
if (! in_array($database, ['vintrack_sprint08_performance', 'vintrack_sprint08_idempotency', 'vintrack_sprint08_backup_source', 'vintrack_sprint08_backup_restore'], true) || ! in_array($action, ['up', 'down'], true)) {
    throw new RuntimeException('Unsafe migration harness database.');
}
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE='.$database);
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = $database;
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$migration = require dirname(__DIR__, 2).'/database/migrations/2026_08_14_130000_add_normalized_value_to_consultations.php';
$migration->{$action}();
echo strtoupper($action);
