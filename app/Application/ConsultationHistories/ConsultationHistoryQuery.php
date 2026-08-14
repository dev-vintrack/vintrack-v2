<?php

namespace App\Application\ConsultationHistories;

use App\Application\NotificationCases\Services\NotificationCaseSettings;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ConsultationHistoryQuery
{
    public const MAX_LENGTH = 100;

    private const ORDER_COLUMNS = [
        0 => 'consulted_at',
        1 => 'user_name',
        2 => 'vin',
        3 => 'license_plate',
        4 => 'make',
        5 => 'model',
        6 => 'model_year',
        7 => 'service_name',
        8 => 'theft_status',
        9 => 'general_status',
    ];

    public function __construct(private readonly NotificationCaseSettings $settings) {}

    public function data(Request $request, ?int $customerUserId): array
    {
        $draw = max(0, (int) $request->input('draw', 0));
        $start = max(0, (int) $request->input('start', 0));
        $requestedLength = (int) $request->input('length', 10);
        $length = min(self::MAX_LENGTH, max(1, $requestedLength));

        $base = $this->baseProjection($customerUserId);
        $recordsTotal = DB::query()->fromSub(clone $base, 'history_total')->count();
        $filtered = $this->applyFilters(DB::query()->fromSub($base, 'history'), $request, $customerUserId === null);
        $recordsFiltered = (clone $filtered)->count();

        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderColumn = self::ORDER_COLUMNS[$orderIndex] ?? 'consulted_at';
        $direction = strtolower((string) $request->input('order.0.dir')) === 'asc' ? 'asc' : 'desc';
        $rows = $filtered->orderBy($orderColumn, $direction)->orderBy('consultation_id', $direction)
            ->offset($start)->limit($length)->get();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn ($row) => ConsultationHistoryRow::fromDatabase($row, $customerUserId))->all(),
        ];
    }

    public function explainQuery(?int $customerUserId): Builder
    {
        return DB::query()->fromSub($this->baseProjection($customerUserId), 'history')
            ->orderByDesc('consulted_at')->orderByDesc('consultation_id')->limit(100);
    }

    private function baseProjection(?int $customerUserId): Builder
    {
        $reuseDays = max(1, $this->settings->reuseDays());
        $plate = "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(%s, '-', ''), ' ', ''), '.', ''), '/', ''))";
        $outerPlate = sprintf($plate, 'c.valor');
        $originPlate = sprintf($plate, 'oc.valor');
        $validatedLimit = DB::connection()->getDriverName() === 'sqlite'
            ? "datetime(nc.validated_at, '+{$reuseDays} days')"
            : "DATE_ADD(nc.validated_at, INTERVAL {$reuseDays} DAY)";
        $caseSql = "SELECT nc.id FROM notification_cases nc INNER JOIN consultations oc ON oc.id = nc.consultation_id
            WHERE oc.created_at <= c.created_at
              AND (nc.consultation_id = c.id OR (c.success = 1 AND c.alerta_robo = 1))
              AND ((LOWER(c.criterio) IN ('vin','niv') AND nc.vin_key = UPPER(c.valor))
                OR (LOWER(c.criterio) = 'placa' AND LOWER(oc.criterio) = 'placa'
                  AND oc.provider_service_id = c.provider_service_id AND {$originPlate} = {$outerPlate}))
              AND (nc.consultation_id = c.id OR nc.closed_at IS NULL OR c.created_at <= nc.closed_at)
              AND (nc.consultation_id = c.id OR nc.validated_at IS NULL OR c.created_at <= {$validatedLimit})
            ORDER BY oc.created_at DESC, nc.id DESC LIMIT 1";

        $query = DB::table('consultations as c')
            ->selectRaw("c.id AS consultation_id, c.user_id AS consultation_user_id, c.created_at AS consulted_at,
                c.criterio, c.valor, c.success, c.alerta_robo, u.name AS user_name, u.email AS user_email,
                ps.name AS service_name, ({$caseSql}) AS applicable_case_id")
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->leftJoin('provider_services as ps', 'ps.id', '=', 'c.provider_service_id');

        if ($customerUserId !== null) {
            $query->where('c.user_id', $customerUserId);
        }

        return DB::query()->fromSub($query, 'resolved')
            ->leftJoin('notification_cases as nc', 'nc.id', '=', 'resolved.applicable_case_id')
            ->leftJoinSub(
                DB::table('notification_case_events')->selectRaw("notification_case_id, MIN(CASE WHEN event_type IN ('CASE_SUBMITTED','CASE_RESUBMITTED') THEN occurred_at END) AS first_submitted_at")
                    ->groupBy('notification_case_id'),
                'submit_events', 'submit_events.notification_case_id', '=', 'nc.id'
            )
            ->selectRaw("resolved.*, nc.user_id AS case_owner_user_id, nc.case_number, nc.vin AS case_vin,
                nc.license_plate AS case_license_plate, nc.make, nc.model, nc.model_year, nc.status AS general_status,
                nc.notification_deadline_at, submit_events.first_submitted_at,
                CASE WHEN resolved.criterio IN ('vin','niv') THEN UPPER(resolved.valor) ELSE COALESCE(nc.vin, 'VIN_NOT_AVAILABLE') END AS vin,
                CASE WHEN resolved.criterio = 'placa' THEN resolved.valor ELSE nc.license_plate END AS license_plate,
                CASE WHEN resolved.alerta_robo = 1 THEN 'POSITIVO' ELSE 'NEGATIVO' END AS theft_status");
    }

    private function applyFilters(Builder $query, Request $request, bool $admin): Builder
    {
        $search = mb_substr(trim((string) $request->input('search.value', '')), 0, 100);
        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function (Builder $q) use ($escaped, $admin) {
                $q->where('vin', 'like', "%{$escaped}%")
                    ->orWhere('license_plate', 'like', "%{$escaped}%")
                    ->orWhere('case_number', 'like', "%{$escaped}%")
                    ->orWhere('make', 'like', "%{$escaped}%")
                    ->orWhere('model', 'like', "%{$escaped}%");
                if ($admin) {
                    $q->orWhere('user_name', 'like', "%{$escaped}%")->orWhere('user_email', 'like', "%{$escaped}%");
                }
            });
        }

        $query->when($request->input('date_from'), fn (Builder $q, $v) => $q->whereDate('consulted_at', '>=', $v))
            ->when($request->input('date_to'), fn (Builder $q, $v) => $q->whereDate('consulted_at', '<=', $v))
            ->when($request->input('theft_status'), fn (Builder $q, $v) => $q->where('theft_status', $v))
            ->when($request->input('case_status'), fn (Builder $q, $v) => $v === 'NO_CASE' ? $q->whereNull('applicable_case_id') : $q->where('general_status', $v));

        if ($admin && $request->filled('user_id')) {
            $query->where('consultation_user_id', (int) $request->input('user_id'));
        }
        if ($admin && $request->filled('vin')) {
            $query->where('vin', 'like', '%'.addcslashes(mb_substr((string) $request->input('vin'), 0, 32), '%_\\').'%');
        }
        if ($admin && $request->filled('plate')) {
            $query->where('license_plate', 'like', '%'.addcslashes(mb_substr((string) $request->input('plate'), 0, 32), '%_\\').'%');
        }

        return $query;
    }
}
