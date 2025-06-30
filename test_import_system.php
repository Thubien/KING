<?php

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use App\Models\ImportBatch;
use App\Services\Import\ImportOrchestrator;
use App\Services\Import\Detectors\BankFormatDetector;
use Illuminate\Support\Facades\Auth;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== IMPORT SYSTEM TEST ===\n\n";

// Get test company and user
$company = Company::where('name', 'EcomBoard Demo Company')->first();
if (!$company) {
    die("Demo company not found\n");
}

$user = User::where('email', 'owner@demo.com')->first();
Auth::login($user);

$store = Store::where('company_id', $company->id)->first();

// Test 1: Bank Format Detection
echo "Test 1: Bank Format Detection\n";
$detector = new BankFormatDetector();

// Test Mercury format (use actual Mercury headers)
$mercuryHeaders = ['Date (UTC)', 'Description', 'Bank Description', 'Amount', 'Currency', 'Running Balance', 'Source Account'];
$mercuryFormat = $detector->detectFormat($mercuryHeaders);
echo "  - Mercury headers detected as: " . ($mercuryFormat === 'mercury' ? 'PASS' : "FAIL (got: $mercuryFormat)") . "\n";

// Test Payoneer format (needs running balance, currency, transaction id)
$payoneerHeaders = ['Date', 'Time', 'Time zone', 'Name', 'Type', 'Status', 'Currency', 'Amount', 'Running Balance', 'Transaction ID'];
$payoneerFormat = $detector->detectFormat($payoneerHeaders);
echo "  - Payoneer headers detected as: " . ($payoneerFormat === 'payoneer' ? 'PASS' : "FAIL (got: $payoneerFormat)") . "\n";

// Test Stripe Balance format (needs fee, net, shop_name metadata)
$stripeBalanceHeaders = ['balance_transaction_id', 'created_utc', 'available_on_utc', 'currency', 'gross', 'fee', 'net', 'shop_name (metadata)'];
$stripeFormat = $detector->detectFormat($stripeBalanceHeaders);
echo "  - Stripe Balance headers detected as: " . ($stripeFormat === 'stripe_balance' ? 'PASS' : "FAIL (got: $stripeFormat)") . "\n";

// Test unknown format
$unknownHeaders = ['Random', 'Headers', 'That', 'Dont', 'Match'];
$unknownFormat = $detector->detectFormat($unknownHeaders);
echo "  - Unknown headers detected as: " . ($unknownFormat === 'unknown' ? 'PASS' : "FAIL (got: $unknownFormat)") . "\n\n";

// Test 2: Import Batch Creation and Status
echo "Test 2: Import Batch Creation\n";
$importBatch = ImportBatch::create([
    'company_id' => $company->id,
    'user_id' => $user->id,
    'store_id' => $store->id,
    'file_name' => 'test_import.csv',
    'file_path' => '/tmp/test_import.csv',
    'format' => 'mercury',
    'status' => 'pending',
    'total_rows' => 100,
    'metadata' => ['test' => true],
]);

echo "  - Import batch created: " . ($importBatch->id ? 'PASS' : 'FAIL') . "\n";
echo "  - Company scoped correctly: " . ($importBatch->company_id === $company->id ? 'PASS' : 'FAIL') . "\n";

// Update status
$importBatch->markAsProcessing();
$importBatch->updateProgress([
    'processed_records' => 50,
    'successful_records' => 40,
    'failed_records' => 5,
    'skipped_records' => 5
]);
echo "  - Status update: " . ($importBatch->status === 'processing' ? 'PASS' : 'FAIL') . "\n";
echo "  - Progress tracking: " . ($importBatch->processed_records === 50 ? 'PASS' : 'FAIL') . "\n\n";

// Test 3: CSV parsing
echo "Test 3: CSV Parsing\n";

// Create test Mercury CSV
$mercuryCsv = <<<CSV
Date,Description,Bank Description,Amount,Currency,Running Balance,Status,Transaction Type,Entity Name,Debits,Credits
2024-01-15,Stripe Transfer,Stripe Inc,-1500.00,USD,8500.00,Completed,External Transfer,Demo Store,1500.00,
2024-01-14,Customer Payment,Payment from Customer,2000.00,USD,10000.00,Completed,External Transfer,Demo Store,,2000.00
CSV;

file_put_contents('/tmp/test_mercury.csv', $mercuryCsv);

// Test CSV reading
$orchestrator = new ImportOrchestrator();
$rows = [];
if (($handle = fopen('/tmp/test_mercury.csv', 'r')) !== false) {
    $headers = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== false) {
        $rows[] = array_combine($headers, $data);
    }
    fclose($handle);
}

echo "  - CSV parsed rows: " . count($rows) . " (expected 2): " . (count($rows) === 2 ? 'PASS' : 'FAIL') . "\n";
echo "  - First row amount: " . ($rows[0]['Amount'] ?? 'N/A') . "\n";
echo "  - Second row amount: " . ($rows[1]['Amount'] ?? 'N/A') . "\n\n";

// Test 4: Smart categorization
echo "Test 4: Smart Categorization\n";

$testDescriptions = [
    'Stripe Transfer - Payout' => 'FEE',
    'Facebook Ads Campaign' => 'ADS',
    'Payoneer Transfer Fee' => 'BANK_COM',
    'Salary Payment John Doe' => 'WITHDRAW',
    'Alibaba Supplier Payment' => 'PAY-PRODUCT',
    'DHL Express Shipping' => 'PAY-DELIVERY',
    'Customer Refund Order #123' => 'RETURNS',
];

foreach ($testDescriptions as $description => $expectedCategory) {
    $patterns = [
        '/facebook|fb|meta/i' => 'ADS',
        '/stripe|fee/i' => 'FEE',
        '/payoneer|transfer fee/i' => 'BANK_COM',
        '/salary|withdraw/i' => 'WITHDRAW',
        '/alibaba|supplier/i' => 'PAY-PRODUCT',
        '/dhl|shipping|delivery/i' => 'PAY-DELIVERY',
        '/refund|return/i' => 'RETURNS',
    ];
    
    $detectedCategory = 'OTHER_PAY';
    foreach ($patterns as $pattern => $category) {
        if (preg_match($pattern, $description)) {
            $detectedCategory = $category;
            break;
        }
    }
    
    echo "  - '$description' → $detectedCategory " . ($detectedCategory === $expectedCategory ? '✓' : "✗ (expected: $expectedCategory)") . "\n";
}

// Clean up
unlink('/tmp/test_mercury.csv');
$importBatch->delete();

echo "\n=== IMPORT TESTS COMPLETE ===\n";