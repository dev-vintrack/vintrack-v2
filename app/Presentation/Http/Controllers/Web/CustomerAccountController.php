<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Application\ConsultationHistories\ConsultationHistoryQuery;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use App\Infrastructure\Persistence\Models\WalletLedgerEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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

        return view('customer.consultations');
    }

    public function consultationData(Request $request, ConsultationHistoryQuery $history): JsonResponse
    {
        $this->rejectUserFilter($request);
        $request->validate([
            'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'theft_status' => ['nullable', 'in:POSITIVO,NEGATIVO'],
            'case_status' => ['nullable', 'in:NO_CASE,PENDING,SUBMITTED,UNDER_REVIEW,REJECTED,VALIDATED,CLOSED_NO_FOLLOW_UP'],
        ]);

        return response()->json($history->data($request, (int) Auth::id()));
    }

    public function vinDecoder(Request $request): View
    {
        $this->rejectUserFilter($request);

        $kpis = [
            'total' => 0,
            'valid' => 0,
            'today' => 0,
            'pending' => 0,
        ];

        return view('customer.vin-decoder', compact('kpis'));
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
}
