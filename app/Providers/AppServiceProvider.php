<?php

namespace App\Providers;

use App\Application\NotificationCases\Malware\MalwareScanner;
use App\Infrastructure\Malware\CloudmersiveMalwareScanner;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MalwareScanner::class, CloudmersiveMalwareScanner::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
