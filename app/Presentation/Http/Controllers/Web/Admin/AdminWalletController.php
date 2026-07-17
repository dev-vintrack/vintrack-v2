<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use App\Infrastructure\Persistence\Models\WalletLedgerEntry;
use App\Models\User;
use Illuminate\Http\Request;

class AdminWalletController
{
    public function index(Request $request)
    {
        $query = UserProviderWallet::with(['user', 'provider'])
            ->orderBy('user_id')
            ->orderBy('provider_id');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->input('provider_id'));
        }

        $kpis = $this->buildWalletKpis($query);
        $wallets = $query->paginate(20)->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $providers = Provider::orderBy('name')->get(['id', 'name']);

        return view('admin.wallets.index', compact('wallets', 'users', 'providers', 'kpis'));
    }

    private function buildWalletKpis($query): array
    {
        $all = (clone $query)->get();

        return [
            'total' => $all->count(),
            'users' => $all->pluck('user_id')->unique()->count(),
            'balance' => $all->sum('balance'),
            'low_balance' => $all->filter(fn ($wallet) => $wallet->balance <= $wallet->min_alert)->count(),
        ];
    }

    public function movements(Request $request)
    {
        $query = WalletLedgerEntry::with(['wallet.user', 'wallet.provider'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('user_id')) {
            $query->whereHas('wallet', function ($q) use ($request) {
                $q->where('user_id', $request->input('user_id'));
            });
        }

        if ($request->filled('provider_id')) {
            $query->whereHas('wallet', function ($q) use ($request) {
                $q->where('provider_id', $request->input('provider_id'));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $kpis = $this->buildMovementKpis($query);

        $movements = $query->get();

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $providers = Provider::orderBy('name')->get(['id', 'name']);

        return view('admin.wallets.movements', compact('movements', 'users', 'providers', 'kpis'));
    }

    private function buildMovementKpis($query): array
    {
        $totalMovements = (clone $query)->count();
        $totalCredits = (float) (clone $query)->where('delta', '>', 0)->sum('delta');
        $totalDebits = (float) (clone $query)->where('delta', '<', 0)->sum('delta');
        $netBalance = (float) (clone $query)->sum('delta');

        return [
            'total' => $totalMovements,
            'credits' => $totalCredits,
            'debits' => abs($totalDebits),
            'balance' => $netBalance,
        ];
    }
}
