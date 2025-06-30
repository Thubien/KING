<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Partnership;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and permissions
        $this->artisan('db:seed', ['--class' => 'PermissionsAndRolesSeeder']);
    }

    public function test_partners_can_only_see_their_own_partnerships()
    {
        // Create two companies
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // Create stores for each company
        $store1 = Store::factory()->create(['company_id' => $company1->id]);
        $store2 = Store::factory()->create(['company_id' => $company2->id]);

        // Create partners
        $partner1 = User::factory()->create([
            'company_id' => $company1->id,
            'user_type' => 'partner',
        ]);
        $partner1->assignRole('partner');

        $partner2 = User::factory()->create([
            'company_id' => $company2->id,
            'user_type' => 'partner',
        ]);
        $partner2->assignRole('partner');

        // Create partnerships
        $partnership1 = Partnership::factory()->create([
            'store_id' => $store1->id,
            'user_id' => $partner1->id,
            'status' => 'ACTIVE',
        ]);

        $partnership2 = Partnership::factory()->create([
            'store_id' => $store2->id,
            'user_id' => $partner2->id,
            'status' => 'ACTIVE',
        ]);

        // Test that partner1 can only see their own partnerships
        $this->assertEquals(1, $partner1->getActivePartnerships()->count());
        $this->assertEquals($partnership1->id, $partner1->getActivePartnerships()->first()->id);

        // Test that partner2 can only see their own partnerships
        $this->assertEquals(1, $partner2->getActivePartnerships()->count());
        $this->assertEquals($partnership2->id, $partner2->getActivePartnerships()->first()->id);

        // Test accessible store IDs
        $this->assertEquals([$store1->id], $partner1->getAccessibleStoreIds());
        $this->assertEquals([$store2->id], $partner2->getAccessibleStoreIds());
    }

    public function test_partners_can_only_access_their_store_transactions()
    {
        // Create company and stores
        $company = Company::factory()->create();
        $store1 = Store::factory()->create(['company_id' => $company->id]);
        $store2 = Store::factory()->create(['company_id' => $company->id]);

        // Create partners
        $partner1 = User::factory()->create([
            'company_id' => $company->id,
            'user_type' => 'partner',
        ]);
        $partner1->assignRole('partner');

        $partner2 = User::factory()->create([
            'company_id' => $company->id,
            'user_type' => 'partner',
        ]);
        $partner2->assignRole('partner');

        // Create partnerships
        Partnership::factory()->create([
            'store_id' => $store1->id,
            'user_id' => $partner1->id,
            'status' => 'ACTIVE',
        ]);

        Partnership::factory()->create([
            'store_id' => $store2->id,
            'user_id' => $partner2->id,
            'status' => 'ACTIVE',
        ]);

        // Create transactions
        $transaction1 = Transaction::factory()->create(['store_id' => $store1->id]);
        $transaction2 = Transaction::factory()->create(['store_id' => $store2->id]);

        // Test store access
        $this->assertTrue($partner1->hasStoreAccess($store1->id));
        $this->assertFalse($partner1->hasStoreAccess($store2->id));

        $this->assertTrue($partner2->hasStoreAccess($store2->id));
        $this->assertFalse($partner2->hasStoreAccess($store1->id));
    }

    public function test_company_owner_can_see_all_company_data()
    {
        // Create company
        $company = Company::factory()->create();

        // Create company owner
        $owner = User::factory()->create([
            'company_id' => $company->id,
            'user_type' => 'company_owner',
        ]);
        $owner->assignRole('company_owner');

        // Create stores
        $store1 = Store::factory()->create(['company_id' => $company->id]);
        $store2 = Store::factory()->create(['company_id' => $company->id]);

        // Create partner
        $partner = User::factory()->create([
            'company_id' => $company->id,
            'user_type' => 'partner',
        ]);
        $partner->assignRole('partner');

        // Create partnerships
        Partnership::factory()->create([
            'store_id' => $store1->id,
            'user_id' => $partner->id,
            'status' => 'ACTIVE',
        ]);

        Partnership::factory()->create([
            'store_id' => $store2->id,
            'user_id' => $owner->id,
            'status' => 'ACTIVE',
        ]);

        // Test that owner can access all stores
        $this->assertTrue($owner->hasStoreAccess($store1->id));
        $this->assertTrue($owner->hasStoreAccess($store2->id));

        // Test accessible store IDs includes all company stores
        $accessibleStoreIds = $owner->getAccessibleStoreIds();
        $this->assertContains($store1->id, $accessibleStoreIds);
        $this->assertContains($store2->id, $accessibleStoreIds);
    }

    public function test_partners_from_different_companies_cannot_see_each_other_data()
    {
        // Create two different companies
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // Create stores for each company
        $store1 = Store::factory()->create(['company_id' => $company1->id]);
        $store2 = Store::factory()->create(['company_id' => $company2->id]);

        // Create partners from different companies
        $partner1 = User::factory()->create([
            'company_id' => $company1->id,
            'user_type' => 'partner',
        ]);

        $partner2 = User::factory()->create([
            'company_id' => $company2->id,
            'user_type' => 'partner',
        ]);

        // Test that partners cannot access stores from other companies
        $this->assertFalse($partner1->hasStoreAccess($store2->id));
        $this->assertFalse($partner2->hasStoreAccess($store1->id));

        // Test that accessible store IDs are company-isolated
        $this->assertNotContains($store2->id, $partner1->getAccessibleStoreIds());
        $this->assertNotContains($store1->id, $partner2->getAccessibleStoreIds());
    }

    public function test_monthly_profit_share_calculation_is_accurate()
    {
        // Create company and store
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        // Create partner with 25% ownership
        $partner = User::factory()->create([
            'company_id' => $company->id,
            'user_type' => 'partner',
        ]);
        $partner->assignRole('partner');

        Partnership::factory()->create([
            'store_id' => $store->id,
            'user_id' => $partner->id,
            'ownership_percentage' => 25.00,
            'status' => 'ACTIVE',
        ]);

        // Create transactions for current month
        $currentMonth = now()->startOfMonth();

        // Create sales transactions totaling $1000
        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'income',
            'category' => 'revenue',
            'amount' => 400.00,
            'created_at' => $currentMonth->addDays(5),
        ]);

        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'income',
            'category' => 'revenue',
            'amount' => 600.00,
            'created_at' => $currentMonth->addDays(10),
        ]);

        // Create non-sales transaction (should not affect profit share)
        Transaction::factory()->create([
            'store_id' => $store->id,
            'type' => 'expense',
            'category' => 'operational',
            'amount' => -200.00,
            'created_at' => $currentMonth->addDays(15),
        ]);

        // Test profit share calculation
        $expectedProfitShare = 1000 * 0.25; // 25% of $1000 = $250
        $actualProfitShare = $partner->getTotalMonthlyProfitShare();

        $this->assertEquals($expectedProfitShare, $actualProfitShare);
    }

    public function test_partner_cannot_access_other_partners_personal_data()
    {
        // Create company and store
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        // Create two partners
        $partner1 = User::factory()->create([
            'company_id' => $company->id,
            'user_type' => 'partner',
            'name' => 'Partner One',
        ]);

        $partner2 = User::factory()->create([
            'company_id' => $company->id,
            'user_type' => 'partner',
            'name' => 'Partner Two',
        ]);

        // Create partnerships for both partners in the same store
        Partnership::factory()->create([
            'store_id' => $store->id,
            'user_id' => $partner1->id,
            'ownership_percentage' => 30.00,
            'status' => 'ACTIVE',
        ]);

        Partnership::factory()->create([
            'store_id' => $store->id,
            'user_id' => $partner2->id,
            'ownership_percentage' => 20.00,
            'status' => 'ACTIVE',
        ]);

        // Test that partner1 can only see their own partnerships
        $partner1Partnerships = $partner1->getActivePartnerships();
        $this->assertEquals(1, $partner1Partnerships->count());
        $this->assertEquals($partner1->id, $partner1Partnerships->first()->user_id);
        $this->assertEquals(30.00, $partner1Partnerships->first()->ownership_percentage);

        // Test that partner2 can only see their own partnerships
        $partner2Partnerships = $partner2->getActivePartnerships();
        $this->assertEquals(1, $partner2Partnerships->count());
        $this->assertEquals($partner2->id, $partner2Partnerships->first()->user_id);
        $this->assertEquals(20.00, $partner2Partnerships->first()->ownership_percentage);

        // Test total ownership calculation (should only include own partnerships)
        $this->assertEquals(30.00, $partner1->getTotalOwnershipPercentage());
        $this->assertEquals(20.00, $partner2->getTotalOwnershipPercentage());
    }
}
