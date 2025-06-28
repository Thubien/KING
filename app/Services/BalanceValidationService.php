<?php

namespace App\Services;

use App\Models\Company;
use App\Models\BankAccount;
use App\Models\PaymentProcessorAccount;
use App\Models\Store;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BalanceValidationService
{
    private float $tolerance = 0.01; // 1 cent tolerance for rounding

    /**
     * KRITIK: Gerçek dünya balance validation
     * Bank Accounts + Payment Processor Accounts = Store Balances
     */
    public function validateCompanyBalance(Company $company): array
    {
        $realMoney = $this->calculateTotalRealMoney($company);
        $calculatedBalance = $this->calculateStoreBalances($company);
        
        $difference = abs($realMoney - $calculatedBalance);
        $isValid = $difference <= $this->tolerance;
        
        $result = [
            'is_valid' => $isValid,
            'real_money_total' => $realMoney,
            'calculated_balance' => $calculatedBalance,
            'difference' => $difference,
            'tolerance' => $this->tolerance,
            'breakdown' => $this->getDetailedBreakdown($company),
            'timestamp' => now()
        ];
        
        if (!$isValid) {
            $this->logBalanceDiscrepancy($company, $result);
        }
        
        return $result;
    }

    /**
     * Toplam gerçek para hesaplama
     * Bank Accounts + Payment Processor Accounts (current + pending)
     */
    private function calculateTotalRealMoney(Company $company): float
    {
        // Bank accounts'taki para
        $bankTotal = BankAccount::where('company_id', $company->id)
            ->sum('current_balance');
            
        // Payment processor'lardaki para (current + pending)
        $processorTotal = PaymentProcessorAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->sum(\DB::raw('current_balance + pending_balance'));
            
        return $bankTotal + $processorTotal;
    }

    /**
     * Store'ların hesaplanan balance'ları
     */
    private function calculateStoreBalances(Company $company): float
    {
        return $company->stores->sum(function($store) {
            return $this->calculateStoreBalance($store);
        });
    }

    /**
     * Tek store'un balance hesaplama
     */
    private function calculateStoreBalance($store): float
    {
        $income = Transaction::where('store_id', $store->id)
            ->where('status', 'APPROVED')
            ->whereIn('type', ['INCOME', 'SALES'])
            ->sum('amount');
            
        $expenses = Transaction::where('store_id', $store->id)
            ->where('status', 'APPROVED')
            ->whereIn('type', ['EXPENSE', 'PERSONAL', 'BUSINESS'])
            ->sum('amount');
            
        return $income - $expenses;
    }

    /**
     * Detaylı breakdown
     */
    private function getDetailedBreakdown(Company $company): array
    {
        $bankAccounts = BankAccount::where('company_id', $company->id)->get();
        $processorAccounts = PaymentProcessorAccount::where('company_id', $company->id)
            ->where('is_active', true)->get();
        $stores = $company->stores;

        return [
            'bank_accounts' => $bankAccounts->map(function($account) {
                return [
                    'id' => $account->id,
                    'bank_type' => $account->bank_type,
                    'currency' => $account->currency,
                    'current_balance' => $account->current_balance,
                    'formatted_balance' => $account->getFormattedBalance()
                ];
            })->toArray(),
            
            'payment_processors' => $processorAccounts->map(function($account) {
                return [
                    'id' => $account->id,
                    'processor_type' => $account->processor_type,
                    'currency' => $account->currency,
                    'current_balance' => $account->current_balance,
                    'pending_balance' => $account->pending_balance,
                    'total_balance' => $account->getTotalBalance(),
                    'formatted_current' => $account->getFormattedCurrentBalance(),
                    'formatted_pending' => $account->getFormattedPendingBalance(),
                    'formatted_total' => $account->getFormattedTotalBalance()
                ];
            })->toArray(),
            
            'stores' => $stores->map(function($store) {
                $balance = $this->calculateStoreBalance($store);
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'currency' => $store->currency,
                    'calculated_balance' => $balance,
                    'formatted_balance' => number_format($balance, 2) . ' ' . $store->currency,
                    'transaction_counts' => [
                        'income' => Transaction::where('store_id', $store->id)
                            ->where('status', 'APPROVED')
                            ->whereIn('type', ['INCOME', 'SALES'])
                            ->count(),
                        'expenses' => Transaction::where('store_id', $store->id)
                            ->where('status', 'APPROVED')
                            ->whereIn('type', ['EXPENSE', 'PERSONAL', 'BUSINESS'])
                            ->count()
                    ]
                ];
            })->toArray()
        ];
    }

    /**
     * Balance discrepancy loglama
     */
    private function logBalanceDiscrepancy(Company $company, array $result): void
    {
        Log::error('BALANCE VALIDATION FAILED', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'real_money_total' => $result['real_money_total'],
            'calculated_balance' => $result['calculated_balance'],
            'difference' => $result['difference'],
            'tolerance' => $result['tolerance'],
            'breakdown' => $result['breakdown'],
            'timestamp' => $result['timestamp']
        ]);
        
        // Cache the error for dashboard display
        Cache::put(
            "balance_error_company_{$company->id}", 
            $result, 
            now()->addHours(24)
        );
    }

    /**
     * Otomatik balance check - scheduled job için
     */
    public function runScheduledBalanceCheck(): void
    {
        $companies = Company::all();
        
        foreach ($companies as $company) {
            $result = $this->validateCompanyBalance($company);
            
            if (!$result['is_valid']) {
                // Send notification to company admins
                $this->notifyBalanceDiscrepancy($company, $result);
            }
        }
    }

    /**
     * Balance discrepancy notification
     */
    private function notifyBalanceDiscrepancy(Company $company, array $result): void
    {
        // Bu kısım email/slack notification için
        Log::warning('Balance discrepancy notification sent', [
            'company_id' => $company->id,
            'difference' => $result['difference']
        ]);
    }

    /**
     * Real-time balance check cache
     */
    public function getCachedBalance(Company $company): array
    {
        return Cache::remember(
            "company_balance_{$company->id}",
            300, // 5 minutes cache
            function() use ($company) {
                return $this->validateCompanyBalance($company);
            }
        );
    }

    /**
     * Force balance recalculation
     */
    public function forceRecalculation(Company $company): array
    {
        Cache::forget("company_balance_{$company->id}");
        return $this->validateCompanyBalance($company);
    }

    /**
     * Balance adjustment - admin tool
     */
    public function createBalanceAdjustment(
        Company $company, 
        float $adjustmentAmount, 
        string $reason,
        string $adjustmentType = 'MANUAL_CORRECTION'
    ): void {
        // Create adjustment transaction
        Transaction::create([
            'company_id' => $company->id,
            'store_id' => $company->stores->first()->id, // Primary store
            'amount' => abs($adjustmentAmount),
            'currency' => 'USD',
            'type' => $adjustmentAmount > 0 ? 'INCOME' : 'EXPENSE',
            'category' => 'OTHER_PAY',
            'description' => "Balance Adjustment: {$reason}",
            'transaction_date' => now(),
            'status' => 'APPROVED',
            'created_by' => auth()->id(),
            'is_adjustment' => true,
            'adjustment_type' => $adjustmentType
        ]);
        
        Log::info('Balance adjustment created', [
            'company_id' => $company->id,
            'adjustment_amount' => $adjustmentAmount,
            'reason' => $reason,
            'created_by' => auth()->id()
        ]);
    }
}