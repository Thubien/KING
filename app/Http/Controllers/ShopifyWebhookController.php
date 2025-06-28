<?php

namespace App\Http\Controllers;

use App\Jobs\SyncShopifyStoreData;
use App\Models\Store;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShopifyWebhookController extends Controller
{
    public function handleOrderCreated(Request $request)
    {
        if (!$this->verifyWebhook($request)) {
            Log::warning('Invalid Shopify webhook signature');
            return response('Unauthorized', 401);
        }

        $order = $request->all();
        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        $store = Store::where('shopify_domain', $shopDomain)
            ->where('status', 'active')
            ->first();

        if (!$store) {
            Log::warning('Webhook received for unknown/inactive store', ['shop_domain' => $shopDomain]);
            return response('Store not found', 404);
        }

        try {
            $this->createTransactionFromOrder($store, $order);
            Log::info('Order webhook processed successfully', [
                'store_id' => $store->id,
                'order_id' => $order['id']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process order webhook', [
                'store_id' => $store->id,
                'order_id' => $order['id'],
                'error' => $e->getMessage()
            ]);
            return response('Processing failed', 500);
        }

        return response('OK', 200);
    }

    public function handleOrderUpdated(Request $request)
    {
        if (!$this->verifyWebhook($request)) {
            return response('Unauthorized', 401);
        }

        $order = $request->all();
        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        $store = Store::where('shopify_domain', $shopDomain)
            ->where('status', 'active')
            ->first();

        if (!$store) {
            return response('Store not found', 404);
        }

        try {
            $this->updateTransactionFromOrder($store, $order);
            Log::info('Order update webhook processed', [
                'store_id' => $store->id,
                'order_id' => $order['id']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process order update webhook', [
                'store_id' => $store->id,
                'order_id' => $order['id'],
                'error' => $e->getMessage()
            ]);
            return response('Processing failed', 500);
        }

        return response('OK', 200);
    }

    public function handleOrderPaid(Request $request)
    {
        if (!$this->verifyWebhook($request)) {
            return response('Unauthorized', 401);
        }

        $order = $request->all();
        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        $store = Store::where('shopify_domain', $shopDomain)
            ->where('status', 'active')
            ->first();

        if (!$store) {
            return response('Store not found', 404);
        }

        // Update transaction status to paid
        $transaction = Transaction::where([
            'store_id' => $store->id,
            'external_id' => $order['id']
        ])->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'processing',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'payment_confirmed_at' => now()->toISOString(),
                    'financial_status' => $order['financial_status'] ?? 'paid'
                ])
            ]);
        }

        return response('OK', 200);
    }

    private function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('X-Shopify-Hmac-Sha256');
        $webhookSecret = config('shopify.webhook_secret');

        if (!$signature || !$webhookSecret) {
            return false;
        }

        $calculatedSignature = base64_encode(hash_hmac(
            'sha256',
            $request->getContent(),
            $webhookSecret,
            true
        ));

        return hash_equals($signature, $calculatedSignature);
    }

    private function createTransactionFromOrder(Store $store, array $order): void
    {
        // Check if transaction already exists
        $existingTransaction = Transaction::where([
            'store_id' => $store->id,
            'external_id' => $order['id']
        ])->first();

        if ($existingTransaction) {
            return; // Already processed
        }

        $transactionData = [
            'store_id' => $store->id,
            'company_id' => $store->company_id,
            'external_id' => $order['id'],
            'type' => 'sale',
            'amount_original' => $order['total_price'],
            'currency_original' => $order['currency'],
            'amount_usd' => $this->convertToUSD($order['total_price'], $order['currency']),
            'transaction_date' => Carbon::parse($order['created_at']),
            'description' => "Shopify Order #{$order['name']}",
            'sales_channel' => 'shopify',
            'payment_method' => $this->mapPaymentMethod($order['payment_gateway_names'] ?? []),
            'data_source' => 'webhook',
            'status' => $this->mapOrderStatus($order),
            'customer_info' => $this->extractCustomerInfo($order),
            'metadata' => [
                'shopify_order_id' => $order['id'],
                'order_name' => $order['name'],
                'financial_status' => $order['financial_status'],
                'fulfillment_status' => $order['fulfillment_status'],
                'line_items_count' => count($order['line_items'] ?? []),
                'webhook_received_at' => now()->toISOString()
            ]
        ];

        Transaction::create($transactionData);
    }

    private function updateTransactionFromOrder(Store $store, array $order): void
    {
        $transaction = Transaction::where([
            'store_id' => $store->id,
            'external_id' => $order['id']
        ])->first();

        if (!$transaction) {
            // Create if doesn't exist
            $this->createTransactionFromOrder($store, $order);
            return;
        }

        $transaction->update([
            'status' => $this->mapOrderStatus($order),
            'metadata' => array_merge($transaction->metadata ?? [], [
                'financial_status' => $order['financial_status'],
                'fulfillment_status' => $order['fulfillment_status'],
                'last_updated_at' => now()->toISOString()
            ])
        ]);
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
        if ($currency === 'USD') {
            return (float) $amount;
        }

        // Placeholder conversion - replace with real API
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
}