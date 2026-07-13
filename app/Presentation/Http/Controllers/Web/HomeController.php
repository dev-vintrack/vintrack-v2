<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Domain\Credits\Repositories\WalletRepositoryInterface;
use App\Domain\Providers\Repositories\ProviderRepositoryInterface;
use App\Domain\Providers\Repositories\ProviderServiceRepositoryInterface;
use App\Domain\Providers\ValueObjects\ProviderCode;
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

    public function index(): View
    {
        return view('home', $this->dashboardData());
    }

    public function cliente(): View
    {
        return view('home.cliente', $this->dashboardData());
    }

    public function perito(): View
    {
        return view('home.perito', $this->dashboardData());
    }

    public function oficial(): View
    {
        return view('home.oficial', $this->dashboardData());
    }

    public function ocasional(): View
    {
        return view('home.ocasional');
    }

    public function pending(): View
    {
        return view('home.pending');
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
        foreach ($providers as $provider) {
            $servicesByProvider[$provider->id()->value()] = $this->serviceRepository
                ->findEnabledByProviderId($provider->id()->value());
        }

        return [
            'providers' => $providers,
            'servicesByProvider' => $servicesByProvider,
            'wallets' => $wallets,
        ];
    }
}
