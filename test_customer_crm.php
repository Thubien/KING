<?php

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerTimelineEvent;
use App\Models\Transaction;
use App\Models\ReturnRequest;
use App\Models\StoreCredit;
use Illuminate\Support\Facades\Auth;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CUSTOMER CRM SYSTEM TEST ===\n\n";

// Get test company and user
$company = Company::where('name', 'EcomBoard Demo Company')->first();
if (!$company) {
    die("Demo company not found\n");
}

$user = User::where('email', 'owner@demo.com')->first();
Auth::login($user);

$store = Store::where('company_id', $company->id)->first();

// Test 1: Customer Creation and Relationships
echo "Test 1: Customer Creation and Relationships\n";

// Clean up any existing test customer first
Customer::where('email', 'test@customer.com')->orWhere('phone', '+905551234567')->delete();

$customer = Customer::create([
    'company_id' => $company->id,
    'store_id' => $store->id,
    'name' => 'Test Customer',
    'email' => 'test@customer.com',
    'phone' => '+905559876543',  // Use different phone to avoid conflicts
    'whatsapp_number' => '905559876543',
    'tags' => ['test', 'vip'],
    'source' => 'manual',
    'status' => 'active',
]);

echo "  - Customer created: " . ($customer->id ? 'PASS' : 'FAIL') . "\n";
echo "  - Company relationship: " . ($customer->company_id === $company->id ? 'PASS' : 'FAIL') . "\n";
echo "  - Store relationship: " . ($customer->store_id === $store->id ? 'PASS' : 'FAIL') . "\n";
$tags = $customer->tags;
// 'new' tag is automatically added by Customer model, so we expect 3 tags
echo "  - Tags array: " . (is_array($tags) && count($tags) === 3 && in_array('new', $tags) ? 'PASS' : 'FAIL (got: ' . json_encode($tags) . ')') . "\n\n";

// Test 2: Customer Address
echo "Test 2: Customer Address\n";

$address = CustomerAddress::create([
    'customer_id' => $customer->id,
    'label' => 'Ev',
    'type' => 'both',
    'full_name' => $customer->name,
    'address_line_1' => 'Test Sok. No:123',
    'city' => 'İstanbul',
    'district' => 'Kadıköy',
    'postal_code' => '34710',
    'country' => 'TR',
    'phone' => $customer->phone,
    'is_default' => true,
]);

echo "  - Address created: " . ($address->id ? 'PASS' : 'FAIL') . "\n";
echo "  - Customer relationship: " . ($address->customer_id === $customer->id ? 'PASS' : 'FAIL') . "\n\n";

// Test 3: Timeline Events
echo "Test 3: Timeline Events\n";

$customer->logTimelineEvent(
    'note_added',
    'Test Note',
    'This is a test timeline event',
    ['test' => true]
);

$events = $customer->timelineEvents()->count();
echo "  - Timeline event created: " . ($events >= 2 ? 'PASS' : 'FAIL') . " (found $events events)\n";

$latestEvent = $customer->timelineEvents()->latest()->first();
echo "  - Event type: " . ($latestEvent->event_type === 'note_added' ? 'PASS' : 'FAIL') . "\n";
echo "  - Event data: " . (is_array($latestEvent->event_data) && isset($latestEvent->event_data['test']) ? 'PASS' : 'FAIL') . "\n\n";

// Test 4: Customer Statistics
echo "Test 4: Customer Statistics\n";

// Clean up any existing test transaction
Transaction::where('transaction_id', 'TEST-CRM-001')->delete();

// Create test transaction
$transaction = Transaction::create([
    'store_id' => $store->id,
    'customer_id' => $customer->id,
    'transaction_id' => 'TEST-CRM-001',
    'amount' => 150.00,
    'currency' => 'USD',
    'amount_usd' => 150.00,
    'category' => 'SALES',
    'type' => 'income',
    'status' => 'APPROVED',
    'description' => 'Test CRM Transaction',
    'transaction_date' => now(),
    'created_by' => $user->id,
]);

// Update statistics
$customer->updateStatistics();
$customer->refresh(); // Refresh to get updated values

echo "  - Total orders: " . ($customer->total_orders === 1 ? 'PASS' : "FAIL (got: {$customer->total_orders})") . "\n";
echo "  - Total spent: " . ($customer->total_spent == 150.00 ? 'PASS' : "FAIL (got: {$customer->total_spent})") . "\n";
echo "  - Last order date: " . ($customer->last_order_date ? 'PASS' : 'FAIL') . "\n\n";

// Test 5: RFM Analysis
echo "Test 5: RFM Analysis\n";

$rfmScore = $customer->getRFMScore();
$recencyScore = $customer->getRFMRecencyScore();
$frequencyScore = $customer->getRFMFrequencyScore();
$monetaryScore = $customer->getRFMMonetaryScore();

echo "  - RFM Score calculated: " . (!empty($rfmScore) ? 'PASS' : 'FAIL') . " (Score: $rfmScore)\n";
echo "  - Recency score (1-5): " . ($recencyScore >= 1 && $recencyScore <= 5 ? 'PASS' : "FAIL (got: $recencyScore)") . "\n";
echo "  - Frequency score (1-5): " . ($frequencyScore >= 1 && $frequencyScore <= 5 ? 'PASS' : "FAIL (got: $frequencyScore)") . "\n";
echo "  - Monetary score (1-5): " . ($monetaryScore >= 1 && $monetaryScore <= 5 ? 'PASS' : "FAIL (got: $monetaryScore)") . "\n\n";

// Test 6: Customer Segmentation
echo "Test 6: Customer Segmentation\n";

$segment = $customer->getSegment();
$segmentLabel = $customer->getSegmentLabel();
$segmentColor = $customer->getSegmentColor();

echo "  - Segment identified: " . (!empty($segment) ? 'PASS' : 'FAIL') . " (Segment: $segment)\n";
echo "  - Segment label: " . (!empty($segmentLabel) ? 'PASS' : 'FAIL') . " (Label: $segmentLabel)\n";
echo "  - Segment color: " . (!empty($segmentColor) ? 'PASS' : 'FAIL') . " (Color: $segmentColor)\n\n";

// Test 7: Store Credit
echo "Test 7: Store Credit\n";

$storeCredit = StoreCredit::create([
    'company_id' => $company->id,
    'store_id' => $store->id,
    'customer_id' => $customer->id,
    'code' => 'TEST-CREDIT-001',
    'amount' => 50.00,
    'remaining_amount' => 50.00,
    'currency' => 'USD',
    'customer_name' => $customer->name,
    'customer_email' => $customer->email,
    'customer_phone' => $customer->phone,
    'status' => 'active',
    'expires_at' => now()->addYear(),
    'issued_by' => 'System Test',
]);

echo "  - Store credit created: " . ($storeCredit->id ? 'PASS' : 'FAIL') . "\n";
echo "  - Customer relationship: " . ($storeCredit->customer_id === $customer->id ? 'PASS' : 'FAIL') . "\n";

// Use credit
$used = $storeCredit->use(25.00, 'TEST-ORDER-001');
echo "  - Credit used: " . ($used ? 'PASS' : 'FAIL') . "\n";
echo "  - Remaining amount: " . ($storeCredit->remaining_amount == 25.00 ? 'PASS' : "FAIL (got: {$storeCredit->remaining_amount})") . "\n\n";

// Test 8: Return Request Integration
echo "Test 8: Return Request Integration\n";

$returnRequest = ReturnRequest::create([
    'company_id' => $company->id,
    'store_id' => $store->id,
    'customer_id' => $customer->id,
    'order_number' => $transaction->transaction_id,
    'customer_name' => $customer->name,
    'customer_phone' => $customer->phone,
    'customer_email' => $customer->email,
    'product_name' => 'Test Product',
    'product_sku' => 'TEST-SKU-001',
    'quantity' => 1,
    'refund_amount' => 135.00, // 90% refund
    'currency' => 'USD',
    'return_reason' => 'Defective product',
    'status' => 'pending',
]);

echo "  - Return request created: " . ($returnRequest->id ? 'PASS' : 'FAIL') . "\n";
echo "  - Customer linked: " . ($returnRequest->customer_id === $customer->id ? 'PASS' : 'FAIL') . "\n\n";

// Test 9: Multi-tenant Isolation
echo "Test 9: Multi-tenant Customer Isolation\n";

// Create another company and customer
$otherCompany = Company::create(['name' => 'Other Test Company', 'slug' => 'other-test', 'domain' => 'other.test']);
$otherStore = Store::create(['company_id' => $otherCompany->id, 'name' => 'Other Store']);
$otherCustomer = Customer::create([
    'company_id' => $otherCompany->id,
    'store_id' => $otherStore->id,
    'name' => 'Other Customer',
    'email' => 'other@customer.com',
    'source' => 'manual',
    'status' => 'active',
]);

// Check isolation
$visibleCustomers = Customer::where('email', 'other@customer.com')->count();
echo "  - Other company's customer visible: " . ($visibleCustomers === 0 ? 'PASS - Properly isolated' : 'FAIL - SECURITY BREACH') . "\n";

// Check own customers
$ownCustomers = Customer::where('company_id', $company->id)->count();
echo "  - Own customers accessible: " . ($ownCustomers > 0 ? 'PASS' : 'FAIL') . "\n\n";

// Test 10: Tag Management
echo "Test 10: Tag Management\n";

$customer->addTag('premium');
echo "  - Tag added: " . ($customer->hasTag('premium') ? 'PASS' : 'FAIL') . "\n";

$customer->removeTag('test');
echo "  - Tag removed: " . (!$customer->hasTag('test') ? 'PASS' : 'FAIL') . "\n";

$allTags = Customer::getAllTags($company->id);
echo "  - Company tags retrieved: " . (is_array($allTags) ? 'PASS' : 'FAIL') . "\n";

// Clean up
$transaction->delete();
$returnRequest->delete();
$storeCredit->delete();
$address->delete();
$customer->delete();
$otherCompany->forceDelete();

echo "\n=== CRM TESTS COMPLETE ===\n";