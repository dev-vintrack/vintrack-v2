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
        $provider = $this->providerRepository->findByCode(ProviderCode::fromString('PLACAS'));
        $services = $provider
            ? $this->serviceRepository->findEnabledByProviderId($provider->id()->value())
            : [];
        $wallets = $this->walletRepository->findByUser(Auth::id());

        return view('home', [
            'provider' => $provider,
            'services' => $services,
            'wallets' => $wallets,
        ]);
    }
}
