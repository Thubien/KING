<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\BankAccount;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MULTI-TENANT ISOLATION TESTS ===\n\n";

// Test 1: Check global store count
$allStores = Store::withoutGlobalScopes()->count();
$scopedStores = Store::count();
echo "Test 1 - Store counts:\n";
echo "  - Total stores in DB: $allStores\n";
echo "  - Stores with scope (no auth): $scopedStores\n";

// Test 2: User isolation
$demoUser = User::where('email', 'owner@demo.com')->first();
if ($demoUser) {
    Auth::login($demoUser);
    $userStores = Store::count();
    $userCompanyStores = Store::where('company_id', $demoUser->company_id)->count();
    echo "\nTest 2 - Demo user store access:\n";
    echo "  - Stores visible to user: $userStores\n";
    echo "  - Expected (company stores): $userCompanyStores\n";
    echo "  - Test result: " . ($userStores == $userCompanyStores ? "PASS" : "FAIL") . "\n";
}

// Test 3: Super admin access
$superAdmin = User::where('email', 'super@admin.com')->first();
if ($superAdmin) {
    Auth::login($superAdmin);
    $superAdminStores = Store::count();
    echo "\nTest 3 - Super admin store access:\n";
    echo "  - Stores visible to super admin: $superAdminStores\n";
    echo "  - Should see all stores: " . ($superAdminStores == $allStores ? "PASS" : "FAIL") . "\n";
}

// Test 4: Transaction isolation
Auth::login($demoUser);
$allTransactions = Transaction::withoutGlobalScopes()->count();
$userTransactions = Transaction::count();
$userCompanyTransactions = Transaction::whereHas('store', function($q) use ($demoUser) {
    $q->where('company_id', $demoUser->company_id);
})->count();
echo "\nTest 4 - Transaction isolation:\n";
echo "  - Total transactions in DB: $allTransactions\n";
echo "  - Visible to demo user: $userTransactions\n";
echo "  - Expected (company transactions): $userCompanyTransactions\n";

// Test 5: Bank account isolation
$allBankAccounts = BankAccount::withoutGlobalScopes()->count();
$userBankAccounts = BankAccount::count();
echo "\nTest 5 - Bank account isolation:\n";
echo "  - Total bank accounts: $allBankAccounts\n";
echo "  - Visible to demo user: $userBankAccounts\n";

// Test 6: Customer isolation
$allCustomers = Customer::withoutGlobalScopes()->count();
$userCustomers = Customer::count();
echo "\nTest 6 - Customer isolation:\n";
echo "  - Total customers: $allCustomers\n";
echo "  - Visible to demo user: $userCustomers\n";

// Test 7: Cross-company access attempt
$otherCompany = Company::where('id', '!=', $demoUser->company_id)->first();
if ($otherCompany) {
    $otherStore = Store::withoutGlobalScopes()->where('company_id', $otherCompany->id)->first();
    if ($otherStore) {
        try {
            $found = Store::find($otherStore->id);
            echo "\nTest 7 - Cross-company access:\n";
            echo "  - Attempted to access store from another company\n";
            echo "  - Result: " . ($found ? "FAIL - SECURITY BREACH!" : "PASS - Access denied") . "\n";
        } catch (\Exception $e) {
            echo "\nTest 7 - Cross-company access:\n";
            echo "  - Result: PASS - Exception thrown\n";
        }
    }
}

echo "\n=== TESTS COMPLETE ===\n";