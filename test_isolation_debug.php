<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ISOLATION DEBUG TEST ===\n\n";

// Get demo user
$demoUser = User::where('email', 'owner@demo.com')->first();
if (!$demoUser) {
    die("Demo user not found\n");
}

echo "Demo User:\n";
echo "  - ID: {$demoUser->id}\n";
echo "  - Company ID: {$demoUser->company_id}\n";
echo "  - Has super_admin role: " . ($demoUser->hasRole('super_admin') ? 'YES' : 'NO') . "\n\n";

// Login as demo user
Auth::login($demoUser);
echo "Logged in as: " . Auth::user()->email . "\n";
echo "Auth check: " . (Auth::check() ? 'YES' : 'NO') . "\n\n";

// Check company's actual data
$companyStores = Store::withoutGlobalScopes()->where('company_id', $demoUser->company_id)->count();
$companyTransactions = Transaction::withoutGlobalScopes()
    ->whereHas('store', function($q) use ($demoUser) {
        $q->where('company_id', $demoUser->company_id);
    })->count();
$companyCustomers = Customer::withoutGlobalScopes()->where('company_id', $demoUser->company_id)->count();

echo "Company's actual data:\n";
echo "  - Stores: $companyStores\n";
echo "  - Transactions: $companyTransactions\n";
echo "  - Customers: $companyCustomers\n\n";

// Check what user sees with scopes
$visibleStores = Store::count();
$visibleTransactions = Transaction::count();
$visibleCustomers = Customer::count();

echo "User sees with scopes:\n";
echo "  - Stores: $visibleStores\n";
echo "  - Transactions: $visibleTransactions\n";
echo "  - Customers: $visibleCustomers\n\n";

// Check other company data
$otherCompany = Company::where('id', '!=', $demoUser->company_id)->first();
if ($otherCompany) {
    $otherStores = Store::withoutGlobalScopes()->where('company_id', $otherCompany->id)->count();
    $otherTransactions = Transaction::withoutGlobalScopes()
        ->whereHas('store', function($q) use ($otherCompany) {
            $q->where('company_id', $otherCompany->id);
        })->count();
    $otherCustomers = Customer::withoutGlobalScopes()->where('company_id', $otherCompany->id)->count();
    
    echo "Other company ({$otherCompany->name}) has:\n";
    echo "  - Stores: $otherStores\n";
    echo "  - Transactions: $otherTransactions\n";
    echo "  - Customers: $otherCustomers\n\n";
}

// Test super admin
$superAdmin = User::where('email', 'super@admin.com')->first();
if ($superAdmin) {
    Auth::login($superAdmin);
    echo "\nLogged in as Super Admin\n";
    echo "  - Has super_admin role: " . ($superAdmin->hasRole('super_admin') ? 'YES' : 'NO') . "\n";
    
    $superStores = Store::count();
    $superTransactions = Transaction::count();
    $superCustomers = Customer::count();
    
    echo "Super admin sees:\n";
    echo "  - Stores: $superStores\n";
    echo "  - Transactions: $superTransactions\n";
    echo "  - Customers: $superCustomers\n";
}

echo "\n=== END DEBUG ===\n";