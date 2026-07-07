<?php

namespace App\Providers;

use App\Domain\Consultas\Repositories\ConsultationRepositoryInterface;
use App\Domain\Credits\Repositories\LedgerRepositoryInterface;
use App\Domain\Credits\Repositories\WalletRepositoryInterface;
use App\Domain\Providers\Repositories\ProviderRepositoryInterface;
use App\Domain\Providers\Repositories\ProviderServiceRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Consultas\ConsultationRepository;
use App\Infrastructure\Persistence\Eloquent\Credits\LedgerRepository;
use App\Infrastructure\Persistence\Eloquent\Credits\WalletRepository;
use App\Infrastructure\Persistence\Eloquent\Providers\ProviderRepository;
use App\Infrastructure\Persistence\Eloquent\Providers\ProviderServiceRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProviderRepositoryInterface::class, ProviderRepository::class);
        $this->app->bind(ProviderServiceRepositoryInterface::class, ProviderServiceRepository::class);
        $this->app->bind(WalletRepositoryInterface::class, WalletRepository::class);
        $this->app->bind(LedgerRepositoryInterface::class, LedgerRepository::class);
        $this->app->bind(ConsultationRepositoryInterface::class, ConsultationRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
