<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Store;
use App\Models\Partnership;
use App\Models\Transaction;

class PartnerDashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        $this->artisan('db:seed', ['--class' => 'PermissionsAndRolesSeeder']);
    }

    public function test_partner_dashboard_performance_with_multiple_stores_and_transactions()
    {
        // Create a company with multiple stores
        $company = Company::factory()->create();
        $stores = Store::factory()->count(5)->create(['company_id' => $company->id]);

        // Create a partner with access to multiple stores
        $partner = User::factory()->create([
            'company_id' => $company->id,
            'user_type' => 'partner'
        ]);
        $partner->assignRole('partner');

        // Create partnerships for multiple stores
        foreach ($stores as $store) {
            Partnership::factory()->create([
                'store_id' => $store->id,
                'user_id' => $partner->id,
                'ownership_percentage' => 20.00,
                'status' => 'ACTIVE'
            ]);
        }

        // Create multiple transactions per store
        foreach ($stores as $store) {
            Transaction::factory()->count(20)->create([
                'store_id' => $store->id,
                'category' => 'revenue',
                'type' => 'income'
            ]);
        }

        $startTime = microtime(true);

        // Test data retrieval operations that would happen on dashboard
        $accessibleStoreIds = $partner->getAccessibleStoreIds();
        $activePartnerships = $partner->getActivePartnerships();
        $totalOwnership = $partner->getTotalOwnershipPercentage();
        $monthlyProfitShare = $partner->getTotalMonthlyProfitShare();

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assertions
        $this->assertCount(5, $accessibleStoreIds);
        $this->assertCount(5, $activePartnerships);
        $this->assertEquals(100.00, $totalOwnership); // 5 stores × 20%
        $this->assertGreaterThan(0, $monthlyProfitShare);

        // Performance assertion - should complete in under 500ms
        $this->assertLessThan(500, $executionTime, "Dashboard queries took {$executionTime}ms, which exceeds 500ms threshold");
        
        echo "\nDashboard performance test completed in {$executionTime}ms";
    }

    public function test_partner_data_isolation_with_high_volume()
    {
        // Create multiple companies with data
        $companies = Company::factory()->count(3)->create();
        $allPartners = collect();

        foreach ($companies as $company) {
            $stores = Store::factory()->count(3)->create(['company_id' => $company->id]);
            $partners = User::factory()->count(5)->create([
                'company_id' => $company->id,
                'user_type' => 'partner'
            ]);

            foreach ($partners as $partner) {
                $partner->assignRole('partner');
                $allPartners->push($partner);
                
                // Create partnerships
                foreach ($stores->random(2) as $store) {
                    Partnership::factory()->create([
                        'store_id' => $store->id,
                        'user_id' => $partner->id,
                        'status' => 'ACTIVE'
                    ]);
                }
            }

            // Create transactions for each store
            foreach ($stores as $store) {
                Transaction::factory()->count(30)->create(['store_id' => $store->id]);
            }
        }

        $startTime = microtime(true);

        // Test that each partner can only see their own data
        foreach ($allPartners as $partner) {
            $accessibleStores = $partner->getAccessibleStoreIds();
            $partnerships = $partner->getActivePartnerships();
            
            // Verify data isolation
            foreach ($partnerships as $partnership) {
                $this->assertEquals($partner->company_id, $partnership->store->company_id);
            }
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Should complete data isolation checks for all partners in under 1 second
        $this->assertLessThan(1000, $executionTime, "Data isolation verification took {$executionTime}ms for {$allPartners->count()} partners");
        
        echo "\nData isolation performance test completed in {$executionTime}ms for {$allPartners->count()} partners";
    }

    public function test_query_count_optimization()
    {
        // Create test data
        $company = Company::factory()->create();
        $stores = Store::factory()->count(3)->create(['company_id' => $company->id]);
        
        $partner = User::factory()->create([
            'company_id' => $company->id,
            'user_type' => 'partner'
        ]);
        $partner->assignRole('partner');

        foreach ($stores as $store) {
            Partnership::factory()->create([
                'store_id' => $store->id,
                'user_id' => $partner->id,
                'status' => 'ACTIVE'
            ]);
            
            Transaction::factory()->count(10)->create([
                'store_id' => $store->id,
                'category' => 'revenue',
                'type' => 'income'
            ]);
        }

        // Enable query log
        \DB::enableQueryLog();

        // Perform dashboard operations
        $accessibleStoreIds = $partner->getAccessibleStoreIds();
        $activePartnerships = $partner->getActivePartnerships();
        $monthlyProfitShare = $partner->getTotalMonthlyProfitShare();

        $queries = \DB::getQueryLog();
        $queryCount = count($queries);

        // Should not exceed reasonable number of queries (N+1 prevention)
        $this->assertLessThan(15, $queryCount, "Dashboard operations generated {$queryCount} queries, which may indicate N+1 problems");
        
        echo "\nQuery optimization test: {$queryCount} queries executed for dashboard operations";
    }
}