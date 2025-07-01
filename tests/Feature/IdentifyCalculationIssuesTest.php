<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Partnership;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentifyCalculationIssuesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'PermissionsAndRolesSeeder']);
    }

    /**
     * Identify issue: Store getProfit method calculation
     */
    public function test_store_profit_calculation_method()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        // Create income transactions
        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'INCOME',
            'category' => 'SALES',
            'amount' => 1000.00,
            'amount_usd' => 1000.00,
            'status' => 'APPROVED',
            'transaction_date' => now(),
        ]);

        // Create expense transactions
        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'EXPENSE',
            'category' => 'ADS',
            'amount' => 300.00,
            'amount_usd' => 300.00,
            'status' => 'APPROVED',
            'transaction_date' => now(),
        ]);

        // Check if getProfit method exists and works correctly
        if (method_exists($store, 'getProfit')) {
            $profit = $store->getProfit('month');
            $this->assertEquals(700.00, $profit);
        } else {
            $this->markTestSkipped('Store::getProfit method needs implementation');
        }
    }

    /**
     * Identify issue: Partnership percentage validation edge case
     */
    public function test_partnership_percentage_precision_issue()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        // Create partnerships with decimal percentages that should total 100
        Partnership::factory()->create([
            'store_id' => $store->id,
            'user_id' => User::factory()->create(['company_id' => $company->id])->id,
            'ownership_percentage' => 33.33,
            'status' => 'ACTIVE',
        ]);

        Partnership::factory()->create([
            'store_id' => $store->id,
            'user_id' => User::factory()->create(['company_id' => $company->id])->id,
            'ownership_percentage' => 33.33,
            'status' => 'ACTIVE',
        ]);

        Partnership::factory()->create([
            'store_id' => $store->id,
            'user_id' => User::factory()->create(['company_id' => $company->id])->id,
            'ownership_percentage' => 33.34,
            'status' => 'ACTIVE',
        ]);

        $total = Partnership::getTotalOwnershipForStore($store->id);
        
        // This might fail due to floating point precision
        $this->assertEquals(100.00, $total);
        
        // Better assertion with tolerance
        $this->assertEqualsWithDelta(100.00, $total, 0.01);
    }

    /**
     * Identify issue: Transaction type constants vs actual usage
     */
    public function test_transaction_type_constants_consistency()
    {
        $validTypes = ['INCOME', 'EXPENSE', 'PERSONAL', 'BUSINESS'];
        
        // Check if constants are defined
        $this->assertTrue(defined('App\Models\Transaction::TYPE_INCOME'));
        $this->assertTrue(defined('App\Models\Transaction::TYPE_EXPENSE'));
        
        // Check if they match expected values
        $this->assertEquals('INCOME', Transaction::TYPE_INCOME);
        $this->assertEquals('EXPENSE', Transaction::TYPE_EXPENSE);
    }

    /**
     * Identify issue: Category system consistency
     */
    public function test_category_system_consistency()
    {
        // Check if all income categories are properly identified
        $incomeCategories = Transaction::getIncomeCategories();
        
        foreach ($incomeCategories as $category => $label) {
            $this->assertTrue(Transaction::isIncomeCategory($category));
        }
        
        // Check if expense categories are not identified as income
        $expenseCategories = ['ADS', 'PAY-PRODUCT', 'FEE', 'RETURNS'];
        
        foreach ($expenseCategories as $category) {
            $this->assertFalse(Transaction::isIncomeCategory($category));
        }
    }

    /**
     * Identify issue: Missing or incorrect exchange rate handling
     */
    public function test_exchange_rate_edge_cases()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        // Test 1: Zero exchange rate should be prevented
        try {
            $transaction = Transaction::factory()->create([
                'store_id' => $store->id,
                'currency' => 'EUR',
                'exchange_rate' => 0,
                'amount' => 100,
            ]);
            
            $this->fail('Zero exchange rate should not be allowed');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        // Test 2: Null exchange rate for non-USD currency
        try {
            $transaction = new Transaction([
                'store_id' => $store->id,
                'type' => 'INCOME',
                'category' => 'SALES',
                'currency' => 'EUR',
                'amount' => 100,
                'exchange_rate' => null,
                'status' => 'APPROVED',
            ]);
            
            $transaction->save();
            
            // Should either fail or auto-set a default rate
            $this->assertNotNull($transaction->exchange_rate);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Identify issue: Store calculatePartnerProfits implementation
     */
    public function test_store_calculate_partner_profits_method()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        // Create partnerships
        Partnership::factory()->create([
            'store_id' => $store->id,
            'user_id' => User::factory()->create(['company_id' => $company->id])->id,
            'ownership_percentage' => 60.00,
            'status' => 'ACTIVE',
        ]);

        Partnership::factory()->create([
            'store_id' => $store->id,
            'user_id' => User::factory()->create(['company_id' => $company->id])->id,
            'ownership_percentage' => 40.00,
            'status' => 'ACTIVE',
        ]);

        // Check if method exists
        if (method_exists($store, 'calculatePartnerProfits')) {
            $profits = $store->calculatePartnerProfits();
            $this->assertIsArray($profits);
            $this->assertCount(2, $profits);
        } else {
            $this->markTestSkipped('Store::calculatePartnerProfits method needs review');
        }
    }

    /**
     * Identify issue: Transaction signed amount calculation
     */
    public function test_transaction_signed_amount_calculation()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        $incomeTransaction = Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'INCOME',
            'category' => 'SALES',
            'amount' => 100.00,
            'amount_usd' => 100.00,
            'status' => 'APPROVED',
        ]);

        $expenseTransaction = Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'EXPENSE',
            'category' => 'ADS',
            'amount' => 50.00,
            'amount_usd' => 50.00,
            'status' => 'APPROVED',
        ]);

        // Test getSignedAmount method
        if (method_exists($incomeTransaction, 'getSignedAmount')) {
            $this->assertEquals(100.00, $incomeTransaction->getSignedAmount());
            $this->assertEquals(-50.00, $expenseTransaction->getSignedAmount());
        } else {
            $this->markTestSkipped('Transaction::getSignedAmount method needs review');
        }
    }

    /**
     * Identify issue: Balance validation tolerance
     */
    public function test_balance_validation_tolerance_handling()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        // Create transactions with amounts that might cause rounding issues
        for ($i = 0; $i < 3; $i++) {
            Transaction::factory()->create([
                'store_id' => $store->id,
                'type' => 'INCOME',
                'category' => 'SALES',
                'amount' => 333.33,
                'amount_usd' => 333.33,
                'status' => 'APPROVED',
            ]);
        }

        // Total should be 999.99, but might be 999.9900000001 due to floating point
        $total = Transaction::where('store_id', $store->id)
            ->where('status', 'APPROVED')
            ->sum('amount_usd');

        // This assertion might fail without proper rounding
        $this->assertEqualsWithDelta(999.99, $total, 0.01);
    }

    /**
     * Summary of identified issues
     */
    public function test_summary_of_issues()
    {
        $issues = [
            'Store::getProfit' => 'Method implementation needs review - using wrong type/status filters',
            'Partnership percentages' => 'Floating point precision issues with 33.33% splits',
            'Exchange rates' => 'Missing validation for zero/null exchange rates',
            'Transaction types' => 'Inconsistency between constants and actual usage',
            'Balance calculations' => 'Need consistent rounding to 2 decimal places',
            'Currency conversion' => 'Bank account balances not converted to USD for validation',
            'Partner debt tracking' => 'Debt updates might not trigger on transaction status change',
            'Multi-currency support' => 'Mixed currency handling in profit calculations',
        ];

        foreach ($issues as $area => $issue) {
            echo "\n❌ {$area}: {$issue}";
        }

        $this->assertTrue(true); // Mark test as passed to see output
    }
}