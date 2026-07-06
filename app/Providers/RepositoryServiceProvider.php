<?php

namespace App\Providers;

use App\Domain\Providers\Repositories\ProviderRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Providers\ProviderRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProviderRepositoryInterface::class, ProviderRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
