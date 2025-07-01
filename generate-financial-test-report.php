<?php

use App\Models\Company;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\Partnership;
use App\Models\BankAccount;
use App\Models\PaymentProcessorAccount;
use App\Services\BalanceValidationService;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n========================================\n";
echo "FINANCIAL CALCULATION TEST REPORT\n";
echo "========================================\n";
echo "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";

// Test 1: Store Balance Calculations
echo "1. STORE BALANCE CALCULATIONS\n";
echo "-------------------------------\n";

$testCompany = Company::first();
if ($testCompany) {
    $stores = $testCompany->stores;
    foreach ($stores as $store) {
        $balance = $store->calculateBalance();
        $transactionCount = $store->transactions()->count();
        $approvedCount = $store->transactions()->where('status', 'APPROVED')->count();
        
        echo "Store: {$store->name}\n";
        echo "  - Total Transactions: {$transactionCount}\n";
        echo "  - Approved Transactions: {$approvedCount}\n";
        echo "  - Calculated Balance: \${$balance}\n\n";
    }
} else {
    echo "No test data found. Run seeders first.\n\n";
}

// Test 2: Partnership Validation
echo "2. PARTNERSHIP PERCENTAGE VALIDATION\n";
echo "------------------------------------\n";

$stores = Store::all();
foreach ($stores as $store) {
    $totalOwnership = Partnership::getTotalOwnershipForStore($store->id);
    $isComplete = $store->isPartnershipComplete();
    $gap = $store->getPartnershipGap();
    
    echo "Store: {$store->name}\n";
    echo "  - Total Ownership: {$totalOwnership}%\n";
    echo "  - Is Complete: " . ($isComplete ? 'Yes' : 'No') . "\n";
    echo "  - Gap: {$gap}%\n";
    
    $partnerships = $store->activePartnerships;
    foreach ($partnerships as $partnership) {
        echo "  - Partner: {$partnership->user->name} ({$partnership->ownership_percentage}%)\n";
    }
    echo "\n";
}

// Test 3: Currency Conversion
echo "3. CURRENCY CONVERSION ANALYSIS\n";
echo "--------------------------------\n";

$currencies = Transaction::distinct('currency')->pluck('currency');
foreach ($currencies as $currency) {
    $count = Transaction::where('currency', $currency)->count();
    $avgRate = Transaction::where('currency', $currency)
        ->where('exchange_rate', '>', 0)
        ->avg('exchange_rate');
    
    echo "Currency: {$currency}\n";
    echo "  - Transaction Count: {$count}\n";
    echo "  - Average Exchange Rate: " . number_format($avgRate, 6) . "\n\n";
}

// Test 4: Balance Validation
echo "4. BALANCE VALIDATION CHECK\n";
echo "----------------------------\n";

$balanceService = new BalanceValidationService();
$companies = Company::all();

foreach ($companies as $company) {
    $result = $balanceService->validateCompanyBalance($company);
    
    echo "Company: {$company->name}\n";
    echo "  - Is Valid: " . ($result['is_valid'] ? '✅ Yes' : '❌ No') . "\n";
    echo "  - Cash Total: \$" . number_format($result['cash_total'], 2) . "\n";
    echo "  - Inventory Total: \$" . number_format($result['inventory_total'], 2) . "\n";
    echo "  - Total Assets: \$" . number_format($result['total_assets'], 2) . "\n";
    echo "  - Calculated Balance: \$" . number_format($result['calculated_balance'], 2) . "\n";
    echo "  - Difference: \$" . number_format($result['difference'], 2) . "\n\n";
}

// Test 5: Category Distribution
echo "5. TRANSACTION CATEGORY DISTRIBUTION\n";
echo "------------------------------------\n";

$categories = Transaction::CATEGORIES;
foreach ($categories as $code => $label) {
    $count = Transaction::where('category', $code)->count();
    $total = Transaction::where('category', $code)->sum('amount_usd');
    
    if ($count > 0) {
        echo "{$label} ({$code})\n";
        echo "  - Count: {$count}\n";
        echo "  - Total: \$" . number_format(abs($total), 2) . "\n\n";
    }
}

// Test 6: Partner Debt Status
echo "6. PARTNER DEBT ANALYSIS\n";
echo "------------------------\n";

$partnerships = Partnership::where('status', 'ACTIVE')->get();
$totalDebt = 0;
$totalCredit = 0;

foreach ($partnerships as $partnership) {
    if ($partnership->debt_balance != 0) {
        echo "Partner: {$partnership->user->name} (Store: {$partnership->store->name})\n";
        echo "  - Debt Balance: " . $partnership->getFormattedDebtBalance() . "\n";
        echo "  - Status: " . ucfirst(str_replace('_', ' ', $partnership->getDebtStatus())) . "\n\n";
        
        if ($partnership->debt_balance > 0) {
            $totalDebt += $partnership->debt_balance;
        } else {
            $totalCredit += abs($partnership->debt_balance);
        }
    }
}

echo "Summary:\n";
echo "  - Total Partner Debt: \$" . number_format($totalDebt, 2) . "\n";
echo "  - Total Partner Credit: \$" . number_format($totalCredit, 2) . "\n\n";

// Test 7: Payment Processor Status
echo "7. PAYMENT PROCESSOR BALANCES\n";
echo "------------------------------\n";

$processors = PaymentProcessorAccount::where('is_active', true)->get();
$totalPending = 0;
$totalCurrent = 0;

foreach ($processors as $processor) {
    echo "{$processor->getDisplayName()} ({$processor->currency})\n";
    echo "  - Current Balance: " . $processor->getFormattedCurrentBalance() . "\n";
    echo "  - Pending Balance: " . $processor->getFormattedPendingBalance() . "\n";
    echo "  - Total Balance: " . $processor->getFormattedTotalBalance() . "\n\n";
    
    $totalPending += $processor->pending_balance;
    $totalCurrent += $processor->current_balance;
}

echo "Total Across All Processors:\n";
echo "  - Current: \$" . number_format($totalCurrent, 2) . "\n";
echo "  - Pending: \$" . number_format($totalPending, 2) . "\n";
echo "  - Total: \$" . number_format($totalCurrent + $totalPending, 2) . "\n\n";

// Test 8: Data Quality Check
echo "8. DATA QUALITY ISSUES\n";
echo "----------------------\n";

$issues = [];

// Check for transactions without exchange rates
$missingRates = Transaction::where('currency', '!=', 'USD')
    ->where(function($q) {
        $q->whereNull('exchange_rate')
          ->orWhere('exchange_rate', 0);
    })->count();
    
if ($missingRates > 0) {
    $issues[] = "Found {$missingRates} non-USD transactions with missing/zero exchange rates";
}

// Check for incomplete partnerships
$incompleteStores = Store::all()->filter(function($store) {
    return !$store->isPartnershipComplete() && $store->activePartnerships()->count() > 0;
});

if ($incompleteStores->count() > 0) {
    $issues[] = "Found {$incompleteStores->count()} stores with incomplete partnership percentages";
}

// Check for negative bank balances
$negativeBanks = BankAccount::where('current_balance', '<', 0)->count();
if ($negativeBanks > 0) {
    $issues[] = "Found {$negativeBanks} bank accounts with negative balances";
}

// Check for unmatched transaction types
$invalidTypes = Transaction::whereNotIn('type', ['INCOME', 'EXPENSE', 'PERSONAL', 'BUSINESS'])->count();
if ($invalidTypes > 0) {
    $issues[] = "Found {$invalidTypes} transactions with invalid type values";
}

if (empty($issues)) {
    echo "✅ No data quality issues found!\n";
} else {
    foreach ($issues as $issue) {
        echo "⚠️  {$issue}\n";
    }
}

echo "\n========================================\n";
echo "END OF REPORT\n";
echo "========================================\n";