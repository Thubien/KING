<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncShopifyStoreData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    public function __construct(
        public Store $store,
        public ?string $since = null
    ) {}

    public function handle(): void
    {
        if ($this->store->status !== 'active' || !$this->store->shopify_access_token) {
            Log::warning('Skipping sync for inactive store', ['store_id' => $this->store->id]);
            return;
        }

        Log::info('Starting Shopify sync', [
            'store_id' => $this->store->id,
            'shop_domain' => $this->store->shopify_domain
        ]);

        try {
            $this->syncOrders();
            
            $this->store->update([
                'last_sync_at' => now(),
                'sync_status' => 'success'
            ]);

            Log::info('Shopify sync completed successfully', ['store_id' => $this->store->id]);

        } catch (\Exception $e) {
            Log::error('Shopify sync failed', [
                'store_id' => $this->store->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->store->update(['sync_status' => 'failed']);
            throw $e;
        }
    }

    private function syncOrders(): void
    {
        $since = $this->since ?: $this->store->last_sync_at?->toISOString() ?: now()->subDays(30)->toISOString();
        
        $url = "https://{$this->store->shopify_domain}/admin/api/" . config('shopify.api_version') . "/orders.json";
        
        $params = [
            'status' => 'any',
            'financial_status' => 'paid',
            'limit' => 250,
            'created_at_min' => $since,
            'fields' => 'id,name,created_at,updated_at,total_price,currency,customer,line_items,payment_gateway_names,financial_status,fulfillment_status'
        ];

        $page = 1;
        $totalSynced = 0;

        do {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => decrypt($this->store->shopify_access_token)
            ])->timeout(30)->get($url, $params);

            if (!$response->successful()) {
                throw new \Exception("Shopify API error: HTTP {$response->status()} - {$response->body()}");
            }

            $data = $response->json();
            $orders = $data['orders'] ?? [];

            foreach ($orders as $order) {
                $this->createOrUpdateTransaction($order);
                $totalSynced++;
            }

            // Check if there are more pages
            $linkHeader = $response->header('Link');
            $hasNextPage = $linkHeader && str_contains($linkHeader, 'rel="next"');
            
            if ($hasNextPage) {
                // Extract next page cursor from Link header
                preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches);
                if (isset($matches[1])) {
                    parse_str(parse_url($matches[1], PHP_URL_QUERY), $nextParams);
                    $params['page_info'] = $nextParams['page_info'] ?? null;
                    unset($params['created_at_min']); // Remove date filter for subsequent pages
                }
            }

            $page++;
            
        } while ($hasNextPage && $page <= 10); // Limit to 10 pages per sync

        Log::info('Orders synced', [
            'store_id' => $this->store->id,
            'total_synced' => $totalSynced,
            'pages_processed' => $page - 1
        ]);
    }

    private function createOrUpdateTransaction(array $order): void
    {
        $existingTransaction = Transaction::where([
            'store_id' => $this->store->id,
            'external_id' => $order['id']
        ])->first();

        $transactionData = [
            'store_id' => $this->store->id,
            'company_id' => $this->store->company_id,
            'external_id' => $order['id'],
            'type' => 'sale',
            'amount_original' => $order['total_price'],
            'currency_original' => $order['currency'],
            'amount_usd' => $this->convertToUSD($order['total_price'], $order['currency']),
            'transaction_date' => Carbon::parse($order['created_at']),
            'description' => "Shopify Order #{$order['name']}",
            'sales_channel' => 'shopify',
            'payment_method' => $this->mapPaymentMethod($order['payment_gateway_names'] ?? []),
            'data_source' => 'shopify_api',
            'status' => $this->mapOrderStatus($order),
            'customer_info' => $this->extractCustomerInfo($order),
            'metadata' => [
                'shopify_order_id' => $order['id'],
                'order_name' => $order['name'],
                'financial_status' => $order['financial_status'],
                'fulfillment_status' => $order['fulfillment_status'],
                'line_items_count' => count($order['line_items'] ?? []),
                'synced_at' => now()->toISOString()
            ]
        ];

        if ($existingTransaction) {
            $existingTransaction->update($transactionData);
        } else {
            Transaction::create($transactionData);
        }
    }

    private function mapPaymentMethod(array $gateways): string
    {
        if (empty($gateways)) return 'other';

        $gateway = strtolower($gateways[0]);
        
        return match(true) {
            str_contains($gateway, 'credit') || str_contains($gateway, 'visa') || str_contains($gateway, 'mastercard') => 'credit_card',
            str_contains($gateway, 'paypal') => 'other',
            str_contains($gateway, 'apple') || str_contains($gateway, 'google') => 'other',
            str_contains($gateway, 'cash') => 'cash',
            str_contains($gateway, 'bank') || str_contains($gateway, 'transfer') => 'bank_transfer',
            str_contains($gateway, 'crypto') || str_contains($gateway, 'bitcoin') => 'crypto',
            default => 'other'
        };
    }

    private function mapOrderStatus(array $order): string
    {
        $financial = $order['financial_status'] ?? '';
        $fulfillment = $order['fulfillment_status'] ?? '';

        if ($financial === 'paid' && $fulfillment === 'fulfilled') {
            return 'completed';
        } elseif ($financial === 'paid') {
            return 'processing';
        } elseif ($financial === 'pending') {
            return 'pending';
        } elseif ($financial === 'refunded') {
            return 'refunded';
        } else {
            return 'pending';
        }
    }

    private function extractCustomerInfo(array $order): ?array
    {
        $customer = $order['customer'] ?? null;
        
        if (!$customer) {
            return null;
        }

        return [
            'shopify_customer_id' => $customer['id'] ?? null,
            'email' => $customer['email'] ?? null,
            'first_name' => $customer['first_name'] ?? null,
            'last_name' => $customer['last_name'] ?? null,
            'phone' => $customer['phone'] ?? null,
            'total_orders' => $customer['orders_count'] ?? 1,
            'total_spent' => $customer['total_spent'] ?? $order['total_price']
        ];
    }

    private function convertToUSD(string $amount, string $currency): float
    {
        // Simple conversion - in production, use a real currency conversion service
        if ($currency === 'USD') {
            return (float) $amount;
        }

        // Placeholder conversion rates - replace with real API
        $rates = [
            'EUR' => 1.08,
            'GBP' => 1.25,
            'CAD' => 0.73,
            'AUD' => 0.65,
            'TRY' => 0.03,
            'UAH' => 0.025
        ];

        $rate = $rates[$currency] ?? 1.0;
        return (float) $amount * $rate;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Shopify sync job failed permanently', [
            'store_id' => $this->store->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->store->update([
            'sync_status' => 'failed',
            'last_sync_error' => $exception->getMessage()
        ]);
    }
}