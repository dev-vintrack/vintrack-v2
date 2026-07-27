<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Infrastructure\Persistence\Models\Consultation;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use App\Infrastructure\Persistence\Models\WalletLedgerEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerAccountController
{
    public function credits(Request $request): View
    {
        $this->rejectUserFilter($request);
        $filters = $request->validate([
            'provider_service_id' => 'nullable|integer',
        ]);

        UserProviderWallet::syncExpiredStatuses(Auth::id());

        $query = UserProviderWallet::with('service.provider')
            ->where('user_id', Auth::id())
            ->orderBy('provider_service_id');

        if (! empty($filters['provider_service_id'])) {
            $query->where('provider_service_id', $filters['provider_service_id']);
        }

        $kpis = $this->walletKpis($query);
        $wallets = $query->get();
        $services = $this->customerServices();

        return view('customer.credits', compact('wallets', 'services', 'kpis'));
    }

    public function movements(Request $request): View
    {
        $this->rejectUserFilter($request);
        $filters = $request->validate([
            'provider_service_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = WalletLedgerEntry::with(['wallet.service.provider'])
            ->whereHas('wallet', fn (Builder $wallet) => $wallet->where('user_id', Auth::id()))
            ->orderBy('created_at', 'desc');

        if (! empty($filters['provider_service_id'])) {
            $query->where('provider_service_id', $filters['provider_service_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $kpis = $this->movementKpis($query);
        $movements = $query->get();
        $services = $this->customerServices();

        return view('customer.movements', compact('movements', 'services', 'kpis'));
    }

    public function consultations(Request $request): View
    {
        $this->rejectUserFilter($request);
        $filters = $request->validate([
            'provider_id' => 'nullable|integer',
            'criterio' => 'nullable|in:placa,niv,vin',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'alerts_only' => 'nullable|boolean',
        ]);

        $query = Consultation::with('provider')
            ->where('user_id', Auth::id());

        if (! empty($filters['provider_id'])) {
            $query->where('provider_id', $filters['provider_id']);
        }

        if (! empty($filters['criterio'])) {
            $query->where('criterio', $filters['criterio']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if ($request->boolean('alerts_only')) {
            $query->where('alerta_robo', true);
        }

        $kpis = $this->consultationKpis($query);
        $consultations = $query->orderBy('created_at', 'desc')->get();
        $providers = Provider::whereIn(
            'id',
            Consultation::where('user_id', Auth::id())->select('provider_id')
        )->orderBy('name')->get(['id', 'name']);
        $criteria = ['placa' => 'Placa', 'niv' => 'NIV', 'vin' => 'VIN'];

        return view('customer.consultations', compact(
            'consultations',
            'providers',
            'criteria',
            'kpis'
        ));
    }

    private function rejectUserFilter(Request $request): void
    {
        if ($request->has('user_id')) {
            abort(400, 'El filtro de usuario no está permitido.');
        }
    }

    private function customerServices()
    {
        return ProviderService::with('provider')
            ->whereIn(
                'id',
                UserProviderWallet::where('user_id', Auth::id())->select('provider_service_id')
            )
            ->orderBy('provider_id')
            ->orderBy('name')
            ->get();
    }

    private function walletKpis(Builder $query): array
    {
        $wallets = (clone $query)->get();

        return [
            'total' => $wallets->count(),
            'balance' => (float) $wallets->sum('balance'),
            'active' => $wallets->where('status', 'active')->count(),
            'low_balance' => $wallets->filter(fn ($wallet) => $wallet->balance <= $wallet->min_alert)->count(),
        ];
    }

    private function movementKpis(Builder $query): array
    {
        $credits = (float) (clone $query)->where('delta', '>', 0)->sum('delta');
        $debits = (float) (clone $query)->where('delta', '<', 0)->sum('delta');

        return [
            'total' => (clone $query)->count(),
            'credits' => $credits,
            'debits' => abs($debits),
            'balance' => $credits + $debits,
        ];
    }

    private function consultationKpis(Builder $query): array
    {
        return [
            'total' => (clone $query)->count(),
            'today' => (clone $query)->whereDate('created_at', today())->count(),
            'success' => (clone $query)->where('success', true)->count(),
            'failure' => (clone $query)->where('success', false)->count(),
            'alerts' => (clone $query)->where('alerta_robo', true)->count(),
        ];
    }
}
