<?php

namespace App\Providers;

use App\Domain\Consultas\Services\ProviderAdapterRegistry;
use App\Infrastructure\External\Providers\Placas\PlacasProviderAdapter;
use Illuminate\Support\ServiceProvider;

class ProviderAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProviderAdapterRegistry::class, function () {
            $registry = new ProviderAdapterRegistry();
            $registry->register(new PlacasProviderAdapter());

            return $registry;
        });
    }

    public function boot(): void
    {
        //
    }
}
