<?php

use App\Application\ConsultationHistories\ConsultationHistoryQuery;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$database = $argv[1] ?? '';
$mode = $argv[2] ?? 'full';
$onlyScenario = $argv[3] ?? null;
if ($database !== 'vintrack_sprint08_performance' || ! in_array($mode, ['full', 'data-only', 'explain-only'], true)) {
    throw new RuntimeException('Unsafe benchmark database.');
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

$service = $app->make(ConsultationHistoryQuery::class);
$baseMethod = new ReflectionMethod($service, 'baseProjection');
$filtersMethod = new ReflectionMethod($service, 'applyFilters');
$candidateMethod = new ReflectionMethod($service, 'searchCandidateIds');
$scenarios = [
    'client_history' => [['draw' => 1, 'start' => 0, 'length' => 25], 1],
    'admin_global' => [['draw' => 1, 'start' => 0, 'length' => 25], null],
    'admin_customer_filter' => [['user_id' => 50, 'start' => 0, 'length' => 25], null],
    'search_vin' => [['search' => ['value' => '1HGCM000000001000'], 'length' => 25], null],
    'search_derived' => [['search' => ['value' => 'MARCA 1'], 'length' => 25], null],
    'search_plate' => [['plate' => 'S001000', 'length' => 25], null],
    'theft_status' => [['theft_status' => 'POSITIVO', 'length' => 25], null],
    'case_status' => [['case_status' => 'VALIDATED', 'length' => 25], null],
    'ordering_pagination' => [['start' => 5000, 'length' => 100, 'order' => [['column' => 2, 'dir' => 'asc']]], null],
];

$results = [];
$capturedQueries = [];
DB::listen(static function ($event) use (&$capturedQueries): void {
    $capturedQueries[] = [
        'milliseconds' => round((float) $event->time, 3),
        'sql' => preg_replace('/\s+/', ' ', $event->sql),
    ];
});
foreach ($scenarios as $name => [$input, $customerId]) {
    if ($onlyScenario !== null && $onlyScenario !== $name) {
        continue;
    }
    $request = Request::create('/benchmark', 'GET', $input);
    $data = null;
    $milliseconds = null;
    $capturedQueries = [];
    if ($mode !== 'explain-only') {
        $started = hrtime(true);
        $data = $service->data($request, $customerId);
        $milliseconds = round((hrtime(true) - $started) / 1_000_000, 3);
    }

    $explain = [];
    if ($mode !== 'data-only') {
        $search = trim((string) $request->input('search.value', ''));
        $caseStatus = trim((string) $request->input('case_status', ''));
        $candidateTerm = $search !== '' ? $search : trim((string) ($request->input('vin') ?: $request->input('plate') ?: ($caseStatus !== 'NO_CASE' ? $caseStatus : '')));
        $scopeUserId = $customerId ?? ($request->filled('user_id') ? (int) $request->input('user_id') : null);
        $candidateIds = $candidateTerm !== ''
            ? $candidateMethod->invoke($service, $candidateTerm, $customerId === null, $scopeUserId)
            : null;
        $base = $baseMethod->invoke($service, $scopeUserId, $candidateIds);
        $filtered = $filtersMethod->invoke($service, DB::query()->fromSub($base, 'history'), $request, $customerId === null);
        $query = $filtered->orderByDesc('consulted_at')->orderByDesc('consultation_id')->limit(100);
        $explain = DB::select('EXPLAIN '.$query->toSql(), $query->getBindings());
    }
    $results[$name] = [
        'milliseconds' => $milliseconds,
        'records_total' => $data['recordsTotal'] ?? null,
        'records_filtered' => $data['recordsFiltered'] ?? null,
        'page_rows' => isset($data['data']) ? count($data['data']) : null,
        'query_timings' => $capturedQueries,
        'explain' => array_map(static fn ($row) => [
            'id' => $row->id, 'select_type' => $row->select_type, 'table' => $row->table,
            'type' => $row->type, 'key' => $row->key, 'rows' => $row->rows, 'extra' => $row->Extra,
        ], $explain),
    ];
}

echo json_encode([
    'database' => $database,
    'counts' => [
        'consultations' => DB::table('consultations')->count(),
        'cases' => DB::table('notification_cases')->count(),
        'events' => DB::table('notification_case_events')->count(),
    ],
    'scenarios' => $results,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
