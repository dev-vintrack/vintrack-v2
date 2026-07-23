<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Domain\Credits\Repositories\WalletRepositoryInterface;
use App\Domain\Providers\Repositories\ProviderRepositoryInterface;
use App\Domain\Providers\Repositories\ProviderServiceRepositoryInterface;
use App\Domain\Providers\ValueObjects\ProviderCode;
use App\Infrastructure\Persistence\Models\ProviderService as ProviderServiceModel;
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

        return view('home.ocasional', $this->dashboardData());
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

        $wallets = $this->walletRepository->findByUser(Auth::id());

        $servicesByProvider = [];
        $providerNames = [];
        $providerCodes = [];
        $serviceOptions = [];
        foreach ($providers as $provider) {
            $servicesByProvider[$provider->id()->value()] = $this->serviceRepository
                ->findEnabledByProviderId($provider->id()->value());
            $providerNames[$provider->id()->value()] = $provider->name();
            $providerCodes[$provider->id()->value()] = $provider->code()->value();
        }

        foreach ($servicesByProvider as $providerId => $serviceList) {
            foreach ($serviceList as $service) {
                $serviceOptions[] = [
                    'id' => $service->id(),
                    'name' => $service->name(),
                    'provider_id' => $providerId,
                    'provider_code' => $providerCodes[$providerId] ?? '',
                    'provider_name' => $providerNames[$providerId] ?? '',
                    'key' => $service->key(),
                ];
            }
        }

        $allowedServiceIds = RoleHelper::allowedServiceIds($user?->id_rol);
        $serviceOptions = array_values(array_filter($serviceOptions, fn ($option) => in_array($option['id'], $allowedServiceIds, true)));

        $activeProviderIds = array_unique(array_map(fn ($option) => $option['provider_id'], $serviceOptions));
        $providers = array_values(array_filter($providers, fn ($provider) => in_array($provider->id()->value(), $activeProviderIds, true)));

        $walletServiceIds = array_unique(array_map(fn ($wallet) => $wallet->providerServiceId(), $wallets));
        $serviceNames = $walletServiceIds
            ? ProviderServiceModel::whereIn('id', $walletServiceIds)
                ->pluck('name', 'id')
                ->all()
            : [];
        $serviceMinAlerts = $walletServiceIds
            ? ProviderServiceModel::whereIn('id', $walletServiceIds)
                ->pluck('min_alert_client', 'id')
                ->all()
            : [];

        return [
            'providers' => $providers,
            'servicesByProvider' => $servicesByProvider,
            'wallets' => $wallets,
            'providerNames' => $providerNames,
            'serviceNames' => $serviceNames,
            'serviceMinAlerts' => $serviceMinAlerts,
            'serviceOptions' => $serviceOptions,
        ];
    }
}
