<?php

namespace App\Providers;

use App\Domain\Consultas\Services\ProviderAdapterRegistry;
use App\Infrastructure\External\Providers\Placas\PlacasProviderAdapter;
use App\Infrastructure\External\Providers\VinData\VinDataProviderAdapter;
use Illuminate\Support\ServiceProvider;

class ProviderAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProviderAdapterRegistry::class, function () {
            $registry = new ProviderAdapterRegistry();
            $registry->register(new PlacasProviderAdapter());
            $registry->register(new VinDataProviderAdapter());

            return $registry;
        });
    }

    public function boot(): void
    {
        //
    }
}
