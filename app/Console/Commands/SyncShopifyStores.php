<?php

namespace App\Console\Commands;

use App\Jobs\SyncShopifyStoreData;
use App\Models\Store;
use Illuminate\Console\Command;

class SyncShopifyStores extends Command
{
    protected $signature = 'shopify:sync {--store-id= : Sync specific store ID} {--since= : Sync orders since date (Y-m-d)}';
    
    protected $description = 'Sync Shopify store data (orders, customers, products)';

    public function handle(): int
    {
        $storeId = $this->option('store-id');
        $since = $this->option('since');

        if ($storeId) {
            $stores = Store::where('id', $storeId)
                ->where('status', 'active')
                ->whereNotNull('shopify_domain')
                ->get();
                
            if ($stores->isEmpty()) {
                $this->error("Store ID {$storeId} not found or not active.");
                return self::FAILURE;
            }
        } else {
            $stores = Store::where('status', 'active')
                ->whereNotNull('shopify_domain')
                ->get();
        }

        if ($stores->isEmpty()) {
            $this->info('No active Shopify stores found to sync.');
            return self::SUCCESS;
        }

        $this->info("Found {$stores->count()} store(s) to sync...");

        $bar = $this->output->createProgressBar($stores->count());
        $bar->start();

        foreach ($stores as $store) {
            try {
                SyncShopifyStoreData::dispatch($store, $since);
                $this->newLine();
                $this->info("Queued sync for: {$store->name} ({$store->shopify_domain})");
                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to queue sync for {$store->name}: {$e->getMessage()}");
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('All sync jobs have been queued. Check queue workers for progress.');

        return self::SUCCESS;
    }
}