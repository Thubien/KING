<?php

namespace App\Providers;

use App\Services\Import\ImportOrchestrator;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Observers\UserObserver;

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
        // Register User Observer
        User::observe(UserObserver::class);
        
        // Login event listener
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\LogSuccessfulLogin::class
        );
    }
}
