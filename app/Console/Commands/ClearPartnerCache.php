<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class ClearPartnerCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'partner:clear-cache {--user= : Clear cache for specific user ID} {--all : Clear all partner-related cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear partner-related cache data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $this->clearAllPartnerCache();
        } elseif ($userId = $this->option('user')) {
            $this->clearUserCache($userId);
        } else {
            $this->clearAllPartnerCache();
        }
    }

    protected function clearAllPartnerCache(): void
    {
        $this->info('Clearing all partner-related cache...');

        // Clear user-specific caches
        User::where('user_type', 'partner')->each(function ($user) {
            $user->clearPartnershipCache();
        });

        // Clear widget caches
        $this->clearWidgetCaches();

        // Clear store-related caches
        $this->clearStoreCaches();

        $this->info('✅ All partner cache cleared successfully!');
    }

    protected function clearUserCache(int $userId): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return;
        }

        $user->clearPartnershipCache();
        $this->info("✅ Cache cleared for user: {$user->name} (ID: {$userId})");
    }

    protected function clearWidgetCaches(): void
    {
        // Get all companies and clear their widget caches
        $companies = \App\Models\Company::pluck('id');
        
        foreach ($companies as $companyId) {
            Cache::forget("widget:pending_invitations:{$companyId}");
        }

        $this->info('Widget caches cleared');
    }

    protected function clearStoreCaches(): void
    {
        $stores = \App\Models\Store::pluck('id');
        
        foreach ($stores as $storeId) {
            Cache::forget("store:{$storeId}:partnerships");
            Cache::forget("store:{$storeId}:total_ownership");
        }

        $this->info('Store caches cleared');
    }
}