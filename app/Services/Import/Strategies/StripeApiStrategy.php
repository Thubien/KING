<?php

namespace App\Services\Import\Strategies;

use App\Models\Store;
use App\Models\Transaction;
use App\Services\Import\Contracts\ApiImportStrategyInterface;
use App\Services\Import\ImportResult;
use Stripe\StripeClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StripeApiStrategy implements ApiImportStrategyInterface
{
    private StripeClient $stripe;
    
    public function import(array $credentials, Store $store): ImportResult
    {
        // 🚨 PREMIUM FEATURE CHECK
        if (!$store->company->canUseApiIntegrations()) {
            return ImportResult::failure(
                '💎 API integrations are only available in Premium plans. Upgrade to unlock Stripe API sync!'
            );
        }

        // Check API rate limits
        if ($store->company->getRemainingApiCalls() < 100) {
            return ImportResult::failure(
                '⚠️ API rate limit exceeded. Upgrade your plan for higher limits or wait until next month.'
            );
        }

        try {
            $this->stripe = new StripeClient($credentials['secret_key']);
            
            // Test the connection first
            $account = $this->stripe->account->retrieve();
            Log::info('Stripe API connection successful', ['account_id' => $account->id]);
            
            $since = $store->last_stripe_sync ?? now()->subDays(30);
            
            $balanceTransactions = $this->stripe->balanceTransactions->all([
                'limit' => 100,
                'created' => ['gte' => $since->timestamp]
            ]);
            
            $imported = 0;
            $failed = 0;
            $duplicates = 0;
            
            foreach ($balanceTransactions->data as $stripeTransaction) {
                // Increment API usage for each transaction processed
                $store->company->incrementApiUsage();
                
                $result = $this->createTransactionFromStripe($stripeTransaction, $store);
                
                switch ($result) {
                    case 'success':
                        $imported++;
                        break;
                    case 'duplicate':
                        $duplicates++;
                        break;
                    case 'failed':
                        $failed++;
                        break;
                }
            }
            
            // Update last sync time
            $store->update(['last_stripe_sync' => now()]);
            
            Log::info('Stripe import completed', [
                'store_id' => $store->id,
                'imported' => $imported,
                'failed' => $failed,
                'duplicates' => $duplicates
            ]);
            
            return ImportResult::success(
                totalRecords: $imported + $failed + $duplicates,
                successfulRecords: $imported,
                failedRecords: $failed,
                duplicateRecords: $duplicates
            );
            
        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::error('Stripe authentication failed', ['error' => $e->getMessage()]);
            return ImportResult::failure(
                '🔑 Invalid Stripe API key. Please check your credentials.'
            );
        } catch (\Exception $e) {
            Log::error('Stripe import failed', ['error' => $e->getMessage()]);
            return ImportResult::failure(
                'Stripe API error: ' . $e->getMessage()
            );
        }
    }
    
    private function createTransactionFromStripe($stripeTransaction, Store $store): string
    {
        // Check for duplicate using external_id (stripe transaction id)
        if (Transaction::where('external_id', $stripeTransaction->id)->exists()) {
            return 'duplicate';
        }
        
        try {
            // 🎯 Smart Auto-categorization based on Stripe transaction type
            $category = $this->categorizeStripeTransaction($stripeTransaction);
            $type = $stripeTransaction->net >= 0 ? 'income' : 'expense';
            $amount = abs($stripeTransaction->net) / 100; // Convert cents to dollars
            
            Transaction::create([
                'store_id' => $store->id,
                'created_by' => auth()->id() ?? 1, // System user for API imports
                'transaction_id' => 'STRIPE-' . strtoupper(substr($stripeTransaction->id, -8)),
                'external_id' => $stripeTransaction->id,
                'amount' => $amount,
                'currency' => strtoupper($stripeTransaction->currency),
                'exchange_rate' => 1.0, // Stripe already handles currency conversion
                'amount_usd' => $amount, // Assuming USD for now
                'category' => $category,
                'type' => $type,
                'status' => 'completed',
                'description' => $this->generateDescription($stripeTransaction),
                'transaction_date' => Carbon::createFromTimestamp($stripeTransaction->created),
                'processed_at' => now(),
                'source' => 'stripe_api',
                'source_details' => json_encode([
                    'stripe_type' => $stripeTransaction->type,
                    'stripe_fee' => $stripeTransaction->fee ?? 0,
                    'gross' => $stripeTransaction->amount ?? 0,
                    'net' => $stripeTransaction->net
                ]),
                'is_reconciled' => true, // API transactions are considered pre-reconciled
                'metadata' => [
                    'stripe_transaction_id' => $stripeTransaction->id,
                    'stripe_type' => $stripeTransaction->type,
                    'import_method' => 'stripe_api',
                    'imported_at' => now()->toISOString()
                ]
            ]);
            
            return 'success';
            
        } catch (\Exception $e) {
            Log::error('Failed to create transaction from Stripe data', [
                'stripe_id' => $stripeTransaction->id,
                'error' => $e->getMessage()
            ]);
            return 'failed';
        }
    }
    
    private function categorizeStripeTransaction($stripeTransaction): string
    {
        // 🎯 Map Stripe transaction types to our 11-category system
        return match($stripeTransaction->type) {
            'charge' => 'revenue',           // Customer payment
            'payment' => 'revenue',          // Payment received
            'invoice_payment' => 'revenue',  // Invoice payment
            
            'refund' => 'refunds_returns',   // Customer refund
            'chargeback' => 'refunds_returns', // Disputed charge
            
            'stripe_fee' => 'fees_commissions',      // Stripe processing fee
            'application_fee' => 'fees_commissions', // Platform fee
            
            'payout' => 'other',             // Transfer to bank (neutral)
            'transfer' => 'other',           // Internal transfer
            
            'adjustment' => 'other',         // Manual adjustment
            'contribution' => 'investments', // Capital contribution
            
            default => 'other'               // Unknown transaction type
        };
    }
    
    private function generateDescription($stripeTransaction): string
    {
        $baseDescription = match($stripeTransaction->type) {
            'charge', 'payment' => '💳 Stripe Payment',
            'invoice_payment' => '📄 Invoice Payment',
            'refund' => '🔄 Stripe Refund', 
            'chargeback' => '⚠️ Chargeback',
            'stripe_fee' => '💰 Stripe Processing Fee',
            'application_fee' => '🏛️ Platform Fee',
            'payout' => '🏦 Stripe Payout',
            'transfer' => '↔️ Transfer',
            'adjustment' => '⚖️ Adjustment',
            'contribution' => '💼 Contribution',
            default => '📊 Stripe Transaction'
        };
        
        // Add amount info for clarity
        $amount = abs($stripeTransaction->net) / 100;
        $currency = strtoupper($stripeTransaction->currency);
        
        return "{$baseDescription} ({$currency} {$amount})";
    }
    
    public function validate(array $credentials): array
    {
        $errors = [];
        
        if (empty($credentials['secret_key'])) {
            $errors[] = 'Stripe secret key is required';
        }
        
        if (!str_starts_with($credentials['secret_key'], 'sk_')) {
            $errors[] = 'Invalid Stripe secret key format (must start with sk_)';
        }
        
        return $errors;
    }
    
    public function getName(): string
    {
        return 'Stripe API Integration 💎';
    }
    
    public function getDescription(): string
    {
        return 'Real-time transaction sync directly from Stripe API. Premium feature with automatic categorization and instant updates.';
    }
    
    public function getRequiredCredentials(): array
    {
        return [
            'secret_key' => [
                'label' => 'Stripe Secret Key',
                'type' => 'password',
                'placeholder' => 'sk_live_... or sk_test_...',
                'help' => 'Your Stripe secret API key for accessing transaction data'
            ]
        ];
    }
    
    public function isPremiumFeature(): bool
    {
        return true;
    }
}