<?php

namespace App\Providers;

use App\Services\Import\ImportOrchestrator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Import Orchestrator as singleton
        $this->app->singleton(ImportOrchestrator::class, function ($app) {
            return new ImportOrchestrator;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
