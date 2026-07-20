<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Domain\Credits\Repositories\WalletRepositoryInterface;
use App\Domain\Providers\Repositories\ProviderRepositoryInterface;
use App\Domain\Providers\Repositories\ProviderServiceRepositoryInterface;
use App\Domain\Providers\ValueObjects\ProviderCode;
use App\Presentation\Support\RoleHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController
{
    public function __construct(
        private readonly ProviderRepositoryInterface $providerRepository,
        private readonly ProviderServiceRepositoryInterface $serviceRepository,
        private readonly WalletRepositoryInterface $walletRepository
    ) {
    }

    public function index(): View|RedirectResponse
    {
        if ($redirect = $this->ensureAllowedRoles(['admin', 'analista', 'soporte'])) {
            return $redirect;
        }

        return view('home', $this->dashboardData());
    }

    public function cliente(): View|RedirectResponse
    {
        if ($redirect = $this->ensureAllowedRoles(['cliente_registrado'])) {
            return $redirect;
        }

        return view('home.cliente', $this->dashboardData());
    }

    public function perito(): View|RedirectResponse
    {
        if ($redirect = $this->ensureAllowedRoles(['perito'])) {
            return $redirect;
        }

        return view('home.perito', $this->dashboardData());
    }

    public function oficial(): View|RedirectResponse
    {
        if ($redirect = $this->ensureAllowedRoles(['oficial'])) {
            return $redirect;
        }

        return view('home.oficial', $this->dashboardData());
    }

    public function ocasional(): View|RedirectResponse
    {
        if ($redirect = $this->ensureAllowedRoles(['ocasional'])) {
            return $redirect;
        }

        return view('home.ocasional');
    }

    public function pending(): View|RedirectResponse
    {
        if ($redirect = $this->ensurePending()) {
            return $redirect;
        }

        return view('home.pending');
    }

    private function ensureAllowedRoles(array $allowedRoles): ?RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'No tienes permisos.');
        }

        if (RoleHelper::requiresApproval($user->rol) && $user->status !== 'active') {
            return redirect()->route('home.pending');
        }

        if (! in_array($user->rol, $allowedRoles, true)) {
            return redirect()->to(RoleHelper::homeRoute($user));
        }

        return null;
    }

    private function ensurePending(): ?RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'No tienes permisos.');
        }

        if (RoleHelper::requiresApproval($user->rol) && $user->status !== 'active') {
            return null;
        }

        return redirect()->to(RoleHelper::homeRoute($user));
    }

    private function dashboardData(): array
    {
        $user = Auth::user();
        $providers = $this->providerRepository->findEnabled();

        if ($user && $user->rol === 'cliente_registrado') {
            $providers = array_filter($providers, function ($provider) {
                return strtolower($provider->code()->value()) === 'placas';
            });
        }

        $wallets = $this->walletRepository->findByUser(Auth::id());

        $servicesByProvider = [];
        $providerNames = [];
        $serviceNames = [];
        foreach ($providers as $provider) {
            $servicesByProvider[$provider->id()->value()] = $this->serviceRepository
                ->findEnabledByProviderId($provider->id()->value());
            $providerNames[$provider->id()->value()] = $provider->name();
        }

        foreach ($servicesByProvider as $serviceList) {
            foreach ($serviceList as $service) {
                $serviceNames[$service->id()] = $service->name();
            }
        }

        return [
            'providers' => $providers,
            'servicesByProvider' => $servicesByProvider,
            'wallets' => $wallets,
            'providerNames' => $providerNames,
            'serviceNames' => $serviceNames,
        ];
    }
}
