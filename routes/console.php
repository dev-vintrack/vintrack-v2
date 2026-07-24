<?php

use App\Infrastructure\Persistence\Models\UserPackage;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

app(Schedule::class)->command('inventory:return-expired-credits')->hourly();
app(Schedule::class)->call(fn () => UserPackage::syncExpiredStatuses())->hourly();
