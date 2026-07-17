<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Infrastructure\Persistence\Models\Consultation;
use App\Infrastructure\Persistence\Models\Provider;
use App\Models\User;
use Illuminate\Http\Request;

class AdminConsultationController
{
    public function index(Request $request)
    {
        $baseQuery = $this->buildFilteredQuery($request);

        $kpis = $this->buildKpis($baseQuery);

        $consultations = $baseQuery
            ->with(['user', 'provider'])
            ->orderBy('created_at', 'desc')
            ->get();

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $providers = Provider::orderBy('name')->get(['id', 'name']);
        $criteria = ['placa' => 'Placa', 'niv' => 'NIV', 'vin' => 'VIN'];

        return view('admin.consultations.index', compact(
            'consultations',
            'users',
            'providers',
            'criteria',
            'kpis'
        ));
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = Consultation::query();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->input('provider_id'));
        }

        if ($request->filled('criterio')) {
            $query->where('criterio', $request->input('criterio'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->boolean('alerts_only')) {
            $query->where('alerta_robo', true);
        }

        return $query;
    }

    private function buildKpis($query): array
    {
        $total = (clone $query)->count();
        $today = (clone $query)->whereDate('created_at', today())->count();
        $previous = (clone $query)->whereDate('created_at', '<', today())->count();
        $success = (clone $query)->where('success', true)->count();
        $failure = (clone $query)->where('success', false)->count();
        $alerts = (clone $query)->where('alerta_robo', true)->count();

        return [
            'total' => $total,
            'today' => $today,
            'previous' => $previous,
            'success' => $success,
            'failure' => $failure,
            'alerts' => $alerts,
        ];
    }
}
