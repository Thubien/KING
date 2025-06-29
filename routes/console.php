<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule Shopify sync to run every hour
Schedule::command('shopify:sync')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Schedule balance validation to run every 6 hours
Schedule::command('balances:validate --notify')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Log::error('Balance validation scheduled task failed');
    });

// Daily balance report at 9 AM
Schedule::command('balances:validate --notify')
    ->dailyAt('09:00')
    ->timezone('America/New_York')
    ->withoutOverlapping();
