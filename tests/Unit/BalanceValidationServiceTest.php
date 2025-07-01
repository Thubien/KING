<?php

namespace Tests\Unit;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\InventoryItem;
use App\Models\PaymentProcessorAccount;
use App\Models\Store;
use App\Models\Transaction;
use App\Services\BalanceValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BalanceValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new BalanceValidationService();
    }

    /**
     * Test the private calculateStoreBalance method via reflection
     */
    public function test_calculate_store_balance_method()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        // Create various transaction types
        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'INCOME',
            'category' => 'SALES',
            'amount' => 1000.00,
            'status' => 'APPROVED',
        ]);

        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'SALES', // Alternative income type
            'category' => 'SALES',
            'amount' => 500.00,
            'status' => 'APPROVED',
        ]);

        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'EXPENSE',
            'category' => 'ADS',
            'amount' => 300.00,
            'status' => 'APPROVED',
        ]);

        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'PERSONAL',
            'category' => 'OTHER_PAY',
            'amount' => 100.00,
            'status' => 'APPROVED',
        ]);

        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'BUSINESS',
            'category' => 'BANK_FEE',
            'amount' => 50.00,
            'status' => 'APPROVED',
        ]);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateStoreBalance');
        $method->setAccessible(true);

        $balance = $method->invoke($this->service, $store);

        // Expected: (1000 + 500) - (300 + 100 + 50) = 1050
        $this->assertEquals(1050.00, $balance);
    }

    /**
     * Test balance validation with inventory value included
     */
    public function test_balance_validation_with_inventory()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        // Create bank account
        BankAccount::create([
            'company_id' => $company->id,
            'bank_type' => 'commercial',
            'currency' => 'USD',
            'current_balance' => 5000.00,
            'is_active' => true,
        ]);

        // Create inventory items
        InventoryItem::factory()->create([
            'store_id' => $store->id,
            'quantity' => 100,
            'unit_cost' => 10.00,
            'total_value' => 1000.00,
            'is_active' => true,
        ]);

        InventoryItem::factory()->create([
            'store_id' => $store->id,
            'quantity' => 50,
            'unit_cost' => 20.00,
            'total_value' => 1000.00,
            'is_active' => true,
        ]);

        // Create transactions to match (cash + inventory = 7000)
        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'INCOME',
            'category' => 'SALES',
            'amount' => 10000.00,
            'status' => 'APPROVED',
        ]);

        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'EXPENSE',
            'category' => 'PAY-PRODUCT',
            'amount' => 3000.00,
            'status' => 'APPROVED',
        ]);

        $result = $this->service->validateCompanyBalance($company);

        $this->assertTrue($result['is_valid']);
        $this->assertEquals(5000.00, $result['cash_total']);
        $this->assertEquals(2000.00, $result['inventory_total']);
        $this->assertEquals(7000.00, $result['total_assets']);
        $this->assertEquals(7000.00, $result['calculated_balance']);
    }

    /**
     * Test tolerance handling for rounding errors
     */
    public function test_tolerance_for_rounding_errors()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        BankAccount::create([
            'company_id' => $company->id,
            'bank_type' => 'commercial',
            'currency' => 'USD',
            'current_balance' => 1000.01, // 1 cent difference
            'is_active' => true,
        ]);

        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'INCOME',
            'category' => 'SALES',
            'amount' => 1000.00,
            'status' => 'APPROVED',
        ]);

        $result = $this->service->validateCompanyBalance($company);

        // Should be valid due to tolerance
        $this->assertTrue($result['is_valid']);
        $this->assertEqualsWithDelta(0.01, $result['difference'], 0.000001);
    }

    /**
     * Test balance validation failure logging
     */
    public function test_balance_discrepancy_logging()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        BankAccount::create([
            'company_id' => $company->id,
            'bank_type' => 'commercial',
            'currency' => 'USD',
            'current_balance' => 1000.00,
            'is_active' => true,
        ]);

        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'INCOME',
            'category' => 'SALES',
            'amount' => 2000.00, // Big discrepancy
            'status' => 'APPROVED',
        ]);

        // Mock the Log facade to verify logging occurs
        \Log::shouldReceive('error')
            ->once()
            ->with('BALANCE VALIDATION FAILED', \Mockery::any());

        $result = $this->service->validateCompanyBalance($company);

        $this->assertFalse($result['is_valid']);
        $this->assertEquals(1000.00, $result['difference']);
    }

    /**
     * Test detailed breakdown structure
     */
    public function test_detailed_breakdown_structure()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        $bankAccount = BankAccount::create([
            'company_id' => $company->id,
            'bank_type' => 'commercial',
            'bank_name' => 'Test Bank',
            'currency' => 'USD',
            'current_balance' => 5000.00,
            'is_active' => true,
        ]);

        $processorAccount = PaymentProcessorAccount::create([
            'company_id' => $company->id,
            'processor_type' => PaymentProcessorAccount::TYPE_STRIPE,
            'currency' => 'USD',
            'current_balance' => 2000.00,
            'pending_balance' => 1000.00,
            'is_active' => true,
        ]);

        $result = $this->service->validateCompanyBalance($company);

        // Check breakdown structure
        $this->assertArrayHasKey('breakdown', $result);
        $this->assertArrayHasKey('bank_accounts', $result['breakdown']);
        $this->assertArrayHasKey('payment_processors', $result['breakdown']);
        $this->assertArrayHasKey('stores', $result['breakdown']);

        // Verify bank account details
        $bankDetails = $result['breakdown']['bank_accounts'][0];
        $this->assertEquals($bankAccount->id, $bankDetails['id']);
        $this->assertEquals('commercial', $bankDetails['bank_type']);
        $this->assertEquals(5000.00, $bankDetails['current_balance']);

        // Verify processor details
        $processorDetails = $result['breakdown']['payment_processors'][0];
        $this->assertEquals($processorAccount->id, $processorDetails['id']);
        $this->assertEquals(2000.00, $processorDetails['current_balance']);
        $this->assertEquals(1000.00, $processorDetails['pending_balance']);
        $this->assertEquals(3000.00, $processorDetails['total_balance']);
    }

    /**
     * Test cache functionality
     */
    public function test_balance_caching()
    {
        $company = Company::factory()->create();
        
        // First call should cache the result
        $result1 = $this->service->getCachedBalance($company);
        
        // Second call should return cached result
        $result2 = $this->service->getCachedBalance($company);
        
        $this->assertEquals($result1, $result2);
        
        // Force recalculation should clear cache
        $result3 = $this->service->forceRecalculation($company);
        
        $this->assertEquals($result1['timestamp']->format('Y-m-d H:i:s'), $result3['timestamp']->format('Y-m-d H:i:s'));
    }

    /**
     * Test multi-currency handling in real money calculation
     */
    public function test_multi_currency_real_money_calculation()
    {
        $company = Company::factory()->create();

        // Create bank accounts in different currencies
        BankAccount::create([
            'company_id' => $company->id,
            'bank_type' => 'commercial',
            'currency' => 'USD',
            'current_balance' => 1000.00,
            'is_active' => true,
        ]);

        BankAccount::create([
            'company_id' => $company->id,
            'bank_type' => 'commercial',
            'currency' => 'EUR',
            'current_balance' => 500.00, // Note: System might need currency conversion
            'is_active' => true,
        ]);

        // Create payment processors in different currencies
        PaymentProcessorAccount::create([
            'company_id' => $company->id,
            'processor_type' => PaymentProcessorAccount::TYPE_STRIPE,
            'currency' => 'USD',
            'current_balance' => 2000.00,
            'pending_balance' => 500.00,
            'is_active' => true,
        ]);

        PaymentProcessorAccount::create([
            'company_id' => $company->id,
            'processor_type' => PaymentProcessorAccount::TYPE_PAYPAL,
            'currency' => 'EUR',
            'current_balance' => 1000.00,
            'pending_balance' => 200.00,
            'is_active' => true,
        ]);

        $result = $this->service->validateCompanyBalance($company);

        // Total should be sum of all balances
        // Note: This test assumes no currency conversion, real implementation might need it
        $expectedTotal = 1000 + 500 + 2000 + 500 + 1000 + 200;
        $this->assertEquals($expectedTotal, $result['cash_total']);
    }

    /**
     * Test inactive account exclusion
     */
    public function test_inactive_accounts_excluded()
    {
        $company = Company::factory()->create();

        BankAccount::create([
            'company_id' => $company->id,
            'bank_type' => 'commercial',
            'currency' => 'USD',
            'current_balance' => 1000.00,
            'is_active' => true,
        ]);

        BankAccount::create([
            'company_id' => $company->id,
            'bank_type' => 'commercial',
            'currency' => 'USD',
            'current_balance' => 5000.00,
            'is_active' => false, // Should be excluded
        ]);

        PaymentProcessorAccount::create([
            'company_id' => $company->id,
            'processor_type' => PaymentProcessorAccount::TYPE_STRIPE,
            'currency' => 'USD',
            'current_balance' => 2000.00,
            'pending_balance' => 0,
            'is_active' => false, // Should be excluded
        ]);

        $result = $this->service->validateCompanyBalance($company);

        // Only active accounts should count
        $this->assertEquals(1000.00, $result['cash_total']);
    }

    /**
     * Test balance adjustment creation
     */
    public function test_create_balance_adjustment()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        // Create user and authenticate
        $user = \App\Models\User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        // Create positive adjustment
        $this->service->createBalanceAdjustment(
            $company,
            500.00,
            'Found missing income',
            'MANUAL_CORRECTION'
        );

        $adjustment = Transaction::where('is_adjustment', true)->first();
        
        $this->assertNotNull($adjustment);
        $this->assertEquals(500.00, $adjustment->amount);
        $this->assertEquals('INCOME', $adjustment->type);
        $this->assertEquals('Balance Adjustment: Found missing income', $adjustment->description);

        // Create negative adjustment
        $this->service->createBalanceAdjustment(
            $company,
            -200.00,
            'Unrecorded expense',
            'BANK_RECONCILIATION'
        );

        $adjustment2 = Transaction::where('is_adjustment', true)
            ->where('adjustment_type', 'BANK_RECONCILIATION')
            ->first();
        
        $this->assertNotNull($adjustment2);
        $this->assertEquals(200.00, $adjustment2->amount);
        $this->assertEquals('EXPENSE', $adjustment2->type);
    }

    /**
     * Test transaction count in breakdown
     */
    public function test_transaction_counts_in_breakdown()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        // Create various transactions
        Transaction::factory()->count(5)->create([
            'store_id' => $store->id,
            'type' => 'INCOME',
            'status' => 'APPROVED',
        ]);

        Transaction::factory()->count(3)->create([
            'store_id' => $store->id,
            'type' => 'EXPENSE',
            'status' => 'APPROVED',
        ]);

        Transaction::factory()->count(2)->create([
            'store_id' => $store->id,
            'type' => 'PERSONAL',
            'status' => 'APPROVED',
        ]);

        $result = $this->service->validateCompanyBalance($company);

        $storeBreakdown = $result['breakdown']['stores'][0];
        $this->assertEquals(5, $storeBreakdown['transaction_counts']['income']);
        $this->assertEquals(5, $storeBreakdown['transaction_counts']['expenses']); // 3 + 2
    }

    /**
     * Test empty company handling
     */
    public function test_empty_company_validation()
    {
        $company = Company::factory()->create();

        $result = $this->service->validateCompanyBalance($company);

        $this->assertTrue($result['is_valid']);
        $this->assertEquals(0.00, $result['cash_total']);
        $this->assertEquals(0.00, $result['inventory_total']);
        $this->assertEquals(0.00, $result['total_assets']);
        $this->assertEquals(0.00, $result['calculated_balance']);
        $this->assertEquals(0.00, $result['difference']);
    }
}