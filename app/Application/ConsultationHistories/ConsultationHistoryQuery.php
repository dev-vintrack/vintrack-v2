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

        $adminUserFilter = $customerUserId === null && $request->filled('user_id') ? (int) $request->input('user_id') : null;
        $scopeUserId = $customerUserId ?? $adminUserFilter;
        $totalQuery = DB::table('consultations');
        if ($customerUserId !== null) {
            $totalQuery->where('user_id', $customerUserId);
        }
        $recordsTotal = $totalQuery->count();
        $consultationScope = DB::table('consultations');
        if ($scopeUserId !== null) {
            $consultationScope->where('user_id', $scopeUserId);
        }
        $consultationScope
            ->when($request->input('date_from'), fn (Builder $q, $v) => $q->where('created_at', '>=', $v.' 00:00:00'))
            ->when($request->input('date_to'), fn (Builder $q, $v) => $q->where('created_at', '<=', $v.' 23:59:59'))
            ->when($request->input('theft_status'), fn (Builder $q, $v) => $q->where('alerta_robo', $v === 'POSITIVO' ? 1 : 0));
        $search = mb_substr(trim((string) $request->input('search.value', '')), 0, 100);
        $caseStatus = trim((string) $request->input('case_status', ''));
        $candidateTerm = $search !== ''
            ? $search
            : trim((string) ($request->input('vin') ?: $request->input('plate') ?: ($caseStatus !== 'NO_CASE' ? $caseStatus : '')));
        $candidateIds = $candidateTerm !== ''
            ? $this->searchCandidateIds(mb_substr($candidateTerm, 0, 100), $customerUserId === null, $scopeUserId)
            : null;
        $base = $this->baseProjection($scopeUserId, $candidateIds);
        $filtered = $this->applyFilters(DB::query()->fromSub($base, 'history'), $request, $customerUserId === null);

        $candidateScope = null;
        if ($candidateIds !== null) {
            $uniqueCandidates = DB::query()->fromSub($candidateIds, 'exact_candidates')->select('id')->groupBy('id');
            $candidateScope = DB::table('consultations as scoped_consultation')
                ->joinSub($uniqueCandidates, 'candidate_ids', 'candidate_ids.id', '=', 'scoped_consultation.id')
                ->when($scopeUserId !== null, fn (Builder $q) => $q->where('scoped_consultation.user_id', $scopeUserId))
                ->when($request->input('date_from'), fn (Builder $q, $v) => $q->where('scoped_consultation.created_at', '>=', $v.' 00:00:00'))
                ->when($request->input('date_to'), fn (Builder $q, $v) => $q->where('scoped_consultation.created_at', '<=', $v.' 23:59:59'))
                ->when($request->input('theft_status'), fn (Builder $q, $v) => $q->where('scoped_consultation.alerta_robo', $v === 'POSITIVO' ? 1 : 0));
        }

        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderColumn = self::ORDER_COLUMNS[$orderIndex] ?? 'consulted_at';
        $direction = strtolower((string) $request->input('order.0.dir')) === 'asc' ? 'asc' : 'desc';
        $requiresProjectionFilter = $this->requiresProjectionFilter($request);
        if ($candidateScope !== null) {
            $recordsFiltered = (clone $candidateScope)->count();
        } elseif (! $requiresProjectionFilter) {
            $recordsFiltered = (clone $consultationScope)->count();
        } else {
            $recordsFiltered = (clone $filtered)->count();
        }

        if (! $requiresProjectionFilter && $candidateScope === null && $orderColumn === 'vin') {
            $ids = $this->orderedVinIds($scopeUserId, $direction, $start, $length);
            $rows = DB::query()->fromSub($this->baseProjection($scopeUserId), 'history')
                ->whereIn('consultation_id', $ids)->orderBy($orderColumn, $direction)
                ->orderBy('consultation_id', $direction)->get();
        } elseif ((! $requiresProjectionFilter || $candidateScope !== null) && $orderColumn === 'consulted_at') {
            $pageScope = $candidateScope !== null ? clone $candidateScope : clone $consultationScope;
            $idColumn = $candidateScope !== null ? 'scoped_consultation.id' : 'id';
            $createdColumn = $candidateScope !== null ? 'scoped_consultation.created_at' : 'created_at';
            $ids = $pageScope
                ->orderBy($createdColumn, $direction)->orderBy($idColumn, $direction)
                ->offset($start)->limit($length)->pluck($idColumn);
            $pageProjection = $candidateScope !== null
                ? $this->applyFilters(DB::query()->fromSub($this->baseProjection($scopeUserId), 'history'), $request, $customerUserId === null)
                : clone $filtered;
            $rows = $pageProjection->whereIn('consultation_id', $ids)
                ->orderBy($orderColumn, $direction)->orderBy('consultation_id', $direction)->get();
        } else {
            $rows = $filtered->orderBy($orderColumn, $direction)->orderBy('consultation_id', $direction)
                ->offset($start)->limit($length)->get();
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn ($row) => ConsultationHistoryRow::fromDatabase($row, $customerUserId))->all(),
        ];
    }

    private function orderedVinIds(?int $scopeUserId, string $direction, int $start, int $length)
    {
        $reuseDays = max(1, $this->settings->reuseDays());
        $validatedLimit = DB::connection()->getDriverName() === 'sqlite'
            ? "datetime(nc.validated_at, '+{$reuseDays} days')"
            : "DATE_ADD(nc.validated_at, INTERVAL {$reuseDays} DAY)";
        $mysql = DB::connection()->getDriverName() === 'mysql';
        $plateFrom = $mysql
            ? 'consultations oc FORCE INDEX (consultations_service_normalized_created_idx) STRAIGHT_JOIN notification_cases nc'
            : 'consultations oc INNER JOIN notification_cases nc';
        $plateVinSql = "SELECT nc.vin FROM {$plateFrom} ON nc.consultation_id = oc.id
            WHERE oc.created_at <= sort_consultation.created_at
              AND sort_consultation.success = 1 AND sort_consultation.alerta_robo = 1
              AND (nc.closed_at IS NULL OR sort_consultation.created_at <= nc.closed_at)
              AND (nc.validated_at IS NULL OR sort_consultation.created_at <= {$validatedLimit})
              AND oc.criterio IN ('placa', 'PLACA', 'Placa')
              AND oc.provider_service_id = sort_consultation.provider_service_id
              AND oc.normalized_value = sort_consultation.normalized_value
            ORDER BY oc.created_at DESC, nc.id DESC LIMIT 1";
        $vin = DB::table('consultations as sort_consultation')
            ->selectRaw('sort_consultation.id, UPPER(sort_consultation.valor) AS sort_value')
            ->whereIn(DB::raw('LOWER(sort_consultation.criterio)'), ['vin', 'niv']);
        $plate = DB::table('consultations as sort_consultation')
            ->selectRaw("sort_consultation.id, COALESCE(({$plateVinSql}), 'VIN_NOT_AVAILABLE') AS sort_value")
            ->whereRaw('LOWER(sort_consultation.criterio) = ?', ['placa']);
        if ($scopeUserId !== null) {
            $vin->where('sort_consultation.user_id', $scopeUserId);
            $plate->where('sort_consultation.user_id', $scopeUserId);
        }

        return DB::query()->fromSub($vin->unionAll($plate), 'vin_order')
            ->orderBy('sort_value', $direction)->orderBy('id', $direction)
            ->offset($start)->limit($length)->pluck('id');
    }

    private function requiresProjectionFilter(Request $request): bool
    {
        return trim((string) $request->input('search.value', '')) !== ''
            || $request->filled('case_status')
            || $request->filled('vin') || $request->filled('plate');
    }

    public function explainQuery(?int $customerUserId): Builder
    {
        return DB::query()->fromSub($this->baseProjection($customerUserId), 'history')
            ->orderByDesc('consulted_at')->orderByDesc('consultation_id')->limit(100);
    }

    private function baseProjection(?int $customerUserId, ?Builder $candidateIds = null): Builder
    {
        $reuseDays = max(1, $this->settings->reuseDays());
        $validatedLimit = DB::connection()->getDriverName() === 'sqlite'
            ? "datetime(nc.validated_at, '+{$reuseDays} days')"
            : "DATE_ADD(nc.validated_at, INTERVAL {$reuseDays} DAY)";
        $mysql = DB::connection()->getDriverName() === 'mysql';
        $vinFrom = $mysql
            ? 'notification_cases nc FORCE INDEX (notification_cases_vin_applicable_idx) INNER JOIN consultations oc'
            : 'notification_cases nc INNER JOIN consultations oc';
        $plateFrom = $mysql
            ? 'consultations oc FORCE INDEX (consultations_service_normalized_created_idx) STRAIGHT_JOIN notification_cases nc'
            : 'consultations oc INNER JOIN notification_cases nc';
        $commonCaseSql = "oc.created_at <= c.created_at
              AND c.success = 1 AND c.alerta_robo = 1
              AND (nc.closed_at IS NULL OR c.created_at <= nc.closed_at)
              AND (nc.validated_at IS NULL OR c.created_at <= {$validatedLimit})";
        $vinCaseSql = "SELECT nc.id FROM {$vinFrom} ON oc.id = nc.consultation_id
            WHERE {$commonCaseSql} AND nc.vin_key = c.normalized_value
            ORDER BY oc.created_at DESC, nc.id DESC LIMIT 1";
        $plateCaseSql = "SELECT nc.id FROM {$plateFrom} ON nc.consultation_id = oc.id
            WHERE {$commonCaseSql}
              AND oc.criterio IN ('placa', 'PLACA', 'Placa')
              AND oc.provider_service_id = c.provider_service_id
              AND oc.normalized_value = c.normalized_value
            ORDER BY oc.created_at DESC, nc.id DESC LIMIT 1";
        $caseSql = "CASE
            WHEN LOWER(c.criterio) IN ('vin','niv') THEN ({$vinCaseSql})
            WHEN LOWER(c.criterio) = 'placa' THEN ({$plateCaseSql})
            ELSE NULL END";

        $query = DB::table('consultations as c')
            ->selectRaw("c.id AS consultation_id, c.user_id AS consultation_user_id, c.created_at AS consulted_at,
                c.criterio, c.valor, c.success, c.alerta_robo, u.name AS user_name, u.email AS user_email,
                ps.name AS service_name, ({$caseSql}) AS applicable_case_id")
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->leftJoin('provider_services as ps', 'ps.id', '=', 'c.provider_service_id');

        if ($customerUserId !== null) {
            $query->where('c.user_id', $customerUserId);
        }
        if ($candidateIds !== null) {
            $materializedCandidates = DB::query()->fromSub($candidateIds, 'search_candidates')
                ->select('id')->groupBy('id');
            $query->joinSub($materializedCandidates, 'candidate_ids', 'candidate_ids.id', '=', 'c.id');
        }

        return DB::query()->fromSub($query, 'resolved')
            ->leftJoin('notification_cases as nc', 'nc.id', '=', 'resolved.applicable_case_id')
            ->selectRaw("resolved.*, nc.user_id AS case_owner_user_id, nc.case_number, nc.vin AS case_vin,
                nc.license_plate AS case_license_plate, nc.make, nc.model, nc.model_year, nc.status AS general_status,
                nc.notification_deadline_at,
                (SELECT MIN(event.occurred_at) FROM notification_case_events event
                    WHERE event.notification_case_id = nc.id
                    AND event.event_type IN ('CASE_SUBMITTED','CASE_RESUBMITTED')) AS first_submitted_at,
                CASE WHEN resolved.criterio IN ('vin','niv') THEN UPPER(resolved.valor) ELSE COALESCE(nc.vin, 'VIN_NOT_AVAILABLE') END AS vin,
                CASE WHEN resolved.criterio = 'placa' THEN resolved.valor ELSE nc.license_plate END AS license_plate,
                CASE WHEN resolved.alerta_robo = 1 THEN 'POSITIVO' ELSE 'NEGATIVO' END AS theft_status");
    }

    private function searchCandidateIds(string $search, bool $admin, ?int $scopeUserId): Builder
    {
        $like = '%'.addcslashes($search, '%_\\').'%';
        $statusTerms = ['PENDIENTE' => 'PENDING', 'RECHAZADO' => 'REJECTED', 'VALIDADO' => 'VALIDATED'];
        $normalizedSearch = strtoupper($search);
        $knownStatus = $statusTerms[$normalizedSearch]
            ?? (in_array($normalizedSearch, ['PENDING', 'SUBMITTED', 'UNDER_REVIEW', 'REJECTED', 'VALIDATED', 'CLOSED_NO_FOLLOW_UP'], true)
                ? $normalizedSearch : null);
        $base = DB::table('consultations as search_consultation')
            ->join('users as search_user', 'search_user.id', '=', 'search_consultation.user_id')
            ->select('search_consultation.id')
            ->where(function (Builder $query) use ($like, $admin) {
                $query->where('search_consultation.valor', 'like', $like);
                if ($admin) {
                    $query->orWhere('search_user.name', 'like', $like)->orWhere('search_user.email', 'like', $like);
                }
            });
        if ($scopeUserId !== null) {
            $base->where('search_consultation.user_id', $scopeUserId);
        }

        $caseSearch = function (Builder $query) use ($like, $knownStatus) {
            $query->where('searched_case.case_number', 'like', $like)
                ->orWhere('searched_case.vin', 'like', $like)
                ->orWhere('searched_case.license_plate', 'like', $like)
                ->orWhere('searched_case.make', 'like', $like)
                ->orWhere('searched_case.model', 'like', $like);
            if ($knownStatus !== null) {
                $query->orWhere('searched_case.status', $knownStatus);
            }
        };
        $reuseDays = max(1, $this->settings->reuseDays());
        $validatedLimit = DB::connection()->getDriverName() === 'sqlite'
            ? "datetime(searched_case.validated_at, '+{$reuseDays} days')"
            : "DATE_ADD(searched_case.validated_at, INTERVAL {$reuseDays} DAY)";
        $derived = function (string $criterion) use ($caseSearch, $validatedLimit, $scopeUserId): Builder {
            $candidateTable = DB::connection()->getDriverName() === 'mysql'
                ? DB::raw('consultations as candidate FORCE INDEX (consultations_normalized_created_idx)')
                : 'consultations as candidate';
            $query = DB::table('notification_cases as searched_case')
                ->join('consultations as origin', 'origin.id', '=', 'searched_case.consultation_id')
                ->join($candidateTable, function ($join) use ($criterion) {
                    if ($criterion === 'vin') {
                        $join->on('candidate.normalized_value', '=', 'searched_case.vin_key');
                    } else {
                        $join->on('candidate.provider_service_id', '=', 'origin.provider_service_id')
                            ->on('candidate.normalized_value', '=', 'origin.normalized_value');
                    }
                })
                ->selectRaw(DB::connection()->getDriverName() === 'mysql' ? 'STRAIGHT_JOIN candidate.id' : 'candidate.id')
                ->where($caseSearch)->where('candidate.success', 1)->where('candidate.alerta_robo', 1)
                ->whereColumn('origin.created_at', '<=', 'candidate.created_at')
                ->where(function (Builder $q) {
                    $q->whereNull('searched_case.closed_at')->orWhereColumn('candidate.created_at', '<=', 'searched_case.closed_at');
                })->where(function (Builder $q) use ($validatedLimit) {
                    $q->whereNull('searched_case.validated_at')->orWhereRaw("candidate.created_at <= {$validatedLimit}");
                })->whereNotExists(function (Builder $newer) use ($criterion) {
                    $reuseDays = max(1, $this->settings->reuseDays());
                    $newerValidatedLimit = DB::connection()->getDriverName() === 'sqlite'
                        ? "datetime(newer_case.validated_at, '+{$reuseDays} days')"
                        : "DATE_ADD(newer_case.validated_at, INTERVAL {$reuseDays} DAY)";
                    $newer->from('notification_cases as newer_case')
                        ->join('consultations as newer_origin', 'newer_origin.id', '=', 'newer_case.consultation_id')
                        ->selectRaw('1')->whereColumn('newer_origin.created_at', '<=', 'candidate.created_at')
                        ->where(function (Builder $q) {
                            $q->whereNull('newer_case.closed_at')->orWhereColumn('candidate.created_at', '<=', 'newer_case.closed_at');
                        })->where(function (Builder $q) use ($newerValidatedLimit) {
                            $q->whereNull('newer_case.validated_at')->orWhereRaw("candidate.created_at <= {$newerValidatedLimit}");
                        })->where(function (Builder $q) {
                            $q->whereColumn('newer_origin.created_at', '>', 'origin.created_at')
                                ->orWhere(function (Builder $tie) {
                                    $tie->whereColumn('newer_origin.created_at', '=', 'origin.created_at')
                                        ->whereColumn('newer_case.id', '>', 'searched_case.id');
                                });
                        });
                    if ($criterion === 'vin') {
                        $newer->whereColumn('newer_case.vin_key', 'candidate.normalized_value');
                    } else {
                        $newer->whereRaw('LOWER(newer_origin.criterio) = ?', ['placa'])
                            ->whereColumn('newer_origin.provider_service_id', 'candidate.provider_service_id')
                            ->whereColumn('newer_origin.normalized_value', 'candidate.normalized_value');
                    }
                });
            if ($criterion === 'vin') {
                $query->whereIn(DB::raw('LOWER(candidate.criterio)'), ['vin', 'niv']);
            } else {
                $query->whereRaw('LOWER(candidate.criterio) = ?', ['placa'])
                    ->whereRaw('LOWER(origin.criterio) = ?', ['placa'])
                    ->whereColumn('origin.provider_service_id', 'candidate.provider_service_id')
                    ->whereColumn('origin.normalized_value', 'candidate.normalized_value');
            }
            if ($scopeUserId !== null) {
                $query->where('candidate.user_id', $scopeUserId);
            }

            return $query;
        };

        return $base->union($derived('vin'))->union($derived('plate'));
    }

    private function applyFilters(Builder $query, Request $request, bool $admin): Builder
    {
        $search = mb_substr(trim((string) $request->input('search.value', '')), 0, 100);
        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $statusTerms = ['PENDIENTE' => 'PENDING', 'RECHAZADO' => 'REJECTED', 'VALIDADO' => 'VALIDATED'];
            $normalizedSearch = strtoupper($escaped);
            $knownStatus = $statusTerms[$normalizedSearch]
                ?? (in_array($normalizedSearch, ['PENDING', 'SUBMITTED', 'UNDER_REVIEW', 'REJECTED', 'VALIDATED', 'CLOSED_NO_FOLLOW_UP'], true)
                    ? $normalizedSearch : null);
            $query->where(function (Builder $q) use ($escaped, $admin, $knownStatus) {
                $q->where('vin', 'like', "%{$escaped}%")
                    ->orWhere('license_plate', 'like', "%{$escaped}%")
                    ->orWhere('case_number', 'like', "%{$escaped}%")
                    ->orWhere('make', 'like', "%{$escaped}%")
                    ->orWhere('model', 'like', "%{$escaped}%");
                if ($knownStatus !== null) {
                    $q->orWhere('general_status', $knownStatus);
                }
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
