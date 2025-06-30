<?php

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CUSTOMER STATISTICS DEBUG ===\n\n";

$company = Company::where('name', 'EcomBoard Demo Company')->first();
$user = User::where('email', 'owner@demo.com')->first();
Auth::login($user);
$store = Store::where('company_id', $company->id)->first();

// Create fresh customer
Customer::where('email', 'stat-test@customer.com')->delete();
$customer = Customer::create([
    'company_id' => $company->id,
    'store_id' => $store->id,
    'name' => 'Statistics Test',
    'email' => 'stat-test@customer.com',
    'phone' => '+905559999999',
    'source' => 'manual',
    'status' => 'active',
]);

echo "Customer created: ID={$customer->id}\n";

// Create transaction
Transaction::where('transaction_id', 'STAT-TEST-001')->delete();
$transaction = Transaction::create([
    'store_id' => $store->id,
    'customer_id' => $customer->id,
    'transaction_id' => 'STAT-TEST-001',
    'amount' => 100.00,
    'currency' => 'USD',
    'amount_usd' => 100.00,
    'category' => 'SALES',
    'type' => 'income',
    'status' => 'APPROVED',
    'description' => 'Test Sale',
    'transaction_date' => now(),
    'created_by' => $user->id,
]);

echo "Transaction created: ID={$transaction->id}\n";
echo "Transaction status: {$transaction->status}\n";
echo "Transaction type: {$transaction->type}\n";
echo "Transaction category: {$transaction->category}\n\n";

// Check transaction relationship
$transCount = $customer->transactions()->count();
echo "Customer transactions count: $transCount\n";

// Check with exact filters
$filteredTrans = $customer->transactions()
    ->where('status', Transaction::STATUS_APPROVED)
    ->where('type', 'income')
    ->where('category', 'SALES')
    ->count();
echo "Filtered transactions count: $filteredTrans\n";

// Check constants
echo "\nTransaction constants:\n";
echo "STATUS_APPROVED = " . Transaction::STATUS_APPROVED . "\n";

// Update statistics
$customer->updateStatistics();
$customer->refresh();

echo "\nAfter updateStatistics:\n";
echo "Total orders: {$customer->total_orders}\n";
echo "Total spent: {$customer->total_spent}\n";
echo "Total spent USD: {$customer->total_spent_usd}\n";

// Clean up
$transaction->delete();
$customer->delete();