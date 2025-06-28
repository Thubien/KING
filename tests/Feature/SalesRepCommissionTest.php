<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Partnership;
use Spatie\Permission\Models\Role;

class SalesRepCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles first
        Role::create(['name' => 'sales_rep']);
        Role::create(['name' => 'company_owner']);
    }

    public function test_sales_rep_role_creation()
    {
        $user = User::factory()->create();
        $user->assignRole('sales_rep');

        $this->assertTrue($user->isSalesRep());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isCompanyOwner());
    }

    public function test_sales_rep_monthly_sales_calculation()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $salesRep = User::factory()->create(['company_id' => $company->id]);
        $salesRep->assignRole('sales_rep');

        // Create transactions for current month
        Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_rep_id' => $salesRep->id,
            'amount' => 100,
            'amount_usd' => 100,
            'type' => 'INCOME',
            'status' => 'APPROVED',
            'data_source' => 'manual_entry',
            'transaction_date' => now()
        ]);

        Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_rep_id' => $salesRep->id,
            'amount' => 200,
            'amount_usd' => 200,
            'type' => 'INCOME',
            'status' => 'APPROVED',
            'data_source' => 'manual_entry',
            'transaction_date' => now()
        ]);

        // Create transaction for last month (should not count)
        Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_rep_id' => $salesRep->id,
            'amount' => 500,
            'amount_usd' => 500,
            'type' => 'INCOME',
            'status' => 'APPROVED',
            'data_source' => 'manual_entry',
            'transaction_date' => now()->subMonth()
        ]);

        $monthlySales = $salesRep->getMonthlySales();
        $this->assertEquals(300, $monthlySales);
    }

    public function test_sales_rep_commission_calculation()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $salesRep = User::factory()->create(['company_id' => $company->id]);
        $salesRep->assignRole('sales_rep');

        // Create partnership with 15% commission
        Partnership::factory()->create([
            'store_id' => $store->id,
            'user_id' => $salesRep->id,
            'ownership_percentage' => 15.0,
            'status' => 'ACTIVE'
        ]);

        // Create sales for this month
        Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_rep_id' => $salesRep->id,
            'amount' => 1000,
            'amount_usd' => 1000,
            'type' => 'INCOME',
            'status' => 'APPROVED',
            'data_source' => 'manual_entry',
            'transaction_date' => now()
        ]);

        $commission = $salesRep->getMonthlyCommission(null, $store->id);
        $this->assertEquals(150, $commission); // 1000 * 15% = 150
    }

    public function test_sales_rep_performance_stats()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $salesRep = User::factory()->create(['company_id' => $company->id]);
        $salesRep->assignRole('sales_rep');

        // Create partnership
        Partnership::factory()->create([
            'store_id' => $store->id,
            'user_id' => $salesRep->id,
            'ownership_percentage' => 20.0,
            'status' => 'ACTIVE'
        ]);

        // Current month: 3 orders totaling $600
        for ($i = 0; $i < 3; $i++) {
            Transaction::factory()->create([
                'store_id' => $store->id,
                'sales_rep_id' => $salesRep->id,
                'amount' => 200,
                'amount_usd' => 200,
                'type' => 'INCOME',
                'status' => 'APPROVED',
                'data_source' => 'manual_entry',
                'transaction_date' => now()
            ]);
        }

        // Last month: $300
        Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_rep_id' => $salesRep->id,
            'amount' => 300,
            'amount_usd' => 300,
            'type' => 'INCOME',
            'status' => 'APPROVED',
            'data_source' => 'manual_entry',
            'transaction_date' => now()->subMonth()
        ]);

        $stats = $salesRep->getSalesRepStats();

        $this->assertEquals(600, $stats['current_month_sales']);
        $this->assertEquals(300, $stats['last_month_sales']);
        $this->assertEquals(100, $stats['growth_percentage']); // (600-300)/300 * 100 = 100%
        $this->assertEquals(3, $stats['total_orders']);
        $this->assertEquals(200, $stats['avg_order_value']);
        $this->assertEquals(120, $stats['commission_earned']); // 600 * 20% = 120
    }

    public function test_sales_rep_customer_stats()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $salesRep = User::factory()->create(['company_id' => $company->id]);
        $salesRep->assignRole('sales_rep');

        // Customer 1: 2 orders (repeat customer)
        Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_rep_id' => $salesRep->id,
            'customer_info' => ['name' => 'Ayşe Yılmaz', 'phone' => '+905321234567'],
            'data_source' => 'manual_entry',
            'type' => 'INCOME'
        ]);

        Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_rep_id' => $salesRep->id,
            'customer_info' => ['name' => 'Ayşe Yılmaz', 'phone' => '+905321234567'],
            'data_source' => 'manual_entry',
            'type' => 'INCOME'
        ]);

        // Customer 2: 1 order
        Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_rep_id' => $salesRep->id,
            'customer_info' => ['name' => 'Mehmet Demir', 'phone' => '+905559876543'],
            'data_source' => 'manual_entry',
            'type' => 'INCOME'
        ]);

        // Customer 3: 1 order
        Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_rep_id' => $salesRep->id,
            'customer_info' => ['name' => 'Fatma Şen', 'phone' => '+905551234567'],
            'data_source' => 'manual_entry',
            'type' => 'INCOME'
        ]);

        $customerStats = $salesRep->getCustomerStats();

        $this->assertEquals(3, $customerStats['total_customers']); // 3 unique customers
        $this->assertEquals(1, $customerStats['repeat_customers']); // 1 repeat customer (Ayşe)
        $this->assertEquals(33.33, $customerStats['repeat_rate']); // 1/3 * 100 = 33.33%
    }

    public function test_sales_rep_dashboard_access()
    {
        $salesRep = User::factory()->create();
        $salesRep->assignRole('sales_rep');

        $companyOwner = User::factory()->create(['user_type' => 'company_owner']);

        $regularUser = User::factory()->create();

        $this->assertTrue($salesRep->canAccessSalesRepDashboard());
        $this->assertTrue($companyOwner->canAccessSalesRepDashboard());
        $this->assertFalse($regularUser->canAccessSalesRepDashboard());
    }

    public function test_sales_rep_can_only_see_own_orders()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        
        $salesRep1 = User::factory()->create(['company_id' => $company->id]);
        $salesRep1->assignRole('sales_rep');
        
        $salesRep2 = User::factory()->create(['company_id' => $company->id]);
        $salesRep2->assignRole('sales_rep');

        // Create orders for each sales rep
        $order1 = Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_rep_id' => $salesRep1->id,
            'data_source' => 'manual_entry',
            'type' => 'INCOME'
        ]);

        $order2 = Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_rep_id' => $salesRep2->id,
            'data_source' => 'manual_entry',
            'type' => 'INCOME'
        ]);

        // Test sales rep 1 can only see their own transactions
        $salesRep1Orders = $salesRep1->salesTransactions()->pluck('id');
        $this->assertContains($order1->id, $salesRep1Orders);
        $this->assertNotContains($order2->id, $salesRep1Orders);

        // Test sales rep 2 can only see their own transactions
        $salesRep2Orders = $salesRep2->salesTransactions()->pluck('id');
        $this->assertContains($order2->id, $salesRep2Orders);
        $this->assertNotContains($order1->id, $salesRep2Orders);
    }

    public function test_default_commission_rate()
    {
        $salesRep = User::factory()->create();
        $salesRep->assignRole('sales_rep');

        // Test default commission rate when no partnership exists
        $defaultRate = $salesRep->getCommissionRate();
        $this->assertEquals(10.0, $defaultRate);
    }
}