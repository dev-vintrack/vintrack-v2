<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Domain\Providers\Repositories\ProviderRepositoryInterface;
use App\Domain\Providers\ValueObjects\ProviderCode;
use Illuminate\View\View;

class HomeController
{
    public function __construct(
        private readonly ProviderRepositoryInterface $providerRepository
    ) {
    }

    public function index(): View
    {
        $provider = $this->providerRepository->findByCode(ProviderCode::fromString('PLACAS'));

        return view('home', [
            'provider' => $provider,
        ]);
    }
}
