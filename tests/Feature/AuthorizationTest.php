<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\ImportBatch;
use App\Models\Partnership;
use App\Models\PaymentProcessorAccount;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company1;
    private Company $company2;
    private Store $store1;
    private Store $store2;
    private Store $store3;
    private User $superAdmin;
    private User $companyOwner1;
    private User $companyOwner2;
    private User $partner1;
    private User $partner2;
    private User $salesRep;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders to set up roles and permissions
        $this->seed(\Database\Seeders\PermissionsAndRolesSeeder::class);
        $this->seed(\Database\Seeders\SalesRepRoleSeeder::class);
        $this->seed(\Database\Seeders\SuperAdminSeeder::class);

        // Create test companies
        $this->company1 = Company::factory()->create(['name' => 'Company 1']);
        $this->company2 = Company::factory()->create(['name' => 'Company 2']);

        // Create test stores
        $this->store1 = Store::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Store 1 - Company 1',
        ]);
        $this->store2 = Store::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Store 2 - Company 1',
        ]);
        $this->store3 = Store::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Store 3 - Company 2',
        ]);

        // Create test users
        // Get the super admin created by the seeder
        $this->superAdmin = User::where('email', 'super@admin.com')->first();
        if (!$this->superAdmin) {
            // Create if doesn't exist
            $superAdminCompany = Company::where('domain', 'superadmin.king.com')->first();
            $this->superAdmin = User::factory()->create([
                'email' => 'test-super@admin.com',
                'company_id' => $superAdminCompany ? $superAdminCompany->id : null,
            ]);
            $this->superAdmin->assignRole('super_admin');
        }

        $this->companyOwner1 = User::factory()->create([
            'email' => 'owner1@company1.com',
            'company_id' => $this->company1->id,
            'user_type' => 'company_owner',
        ]);
        $this->companyOwner1->assignRole('company_owner');

        $this->companyOwner2 = User::factory()->create([
            'email' => 'owner2@company2.com',
            'company_id' => $this->company2->id,
            'user_type' => 'company_owner',
        ]);
        $this->companyOwner2->assignRole('company_owner');

        $this->partner1 = User::factory()->create([
            'email' => 'partner1@company1.com',
            'company_id' => $this->company1->id,
            'user_type' => 'partner',
        ]);
        $this->partner1->assignRole('partner');

        $this->partner2 = User::factory()->create([
            'email' => 'partner2@company1.com',
            'company_id' => $this->company1->id,
            'user_type' => 'partner',
        ]);
        $this->partner2->assignRole('partner');

        $this->salesRep = User::factory()->create([
            'email' => 'salesrep@company1.com',
            'company_id' => $this->company1->id,
            'user_type' => 'viewer', // Using viewer as closest match for sales rep
        ]);
        $this->salesRep->assignRole('sales_rep');

        // Create partnerships
        Partnership::create([
            'store_id' => $this->store1->id,
            'user_id' => $this->partner1->id,
            'ownership_percentage' => 60,
            'status' => 'ACTIVE',
            'partnership_start_date' => now(),
        ]);

        Partnership::create([
            'store_id' => $this->store1->id,
            'user_id' => $this->partner2->id,
            'ownership_percentage' => 40,
            'status' => 'ACTIVE',
            'partnership_start_date' => now(),
        ]);

        // Partner 1 only has access to Store 1, not Store 2
        Partnership::create([
            'store_id' => $this->store2->id,
            'user_id' => $this->partner2->id,
            'ownership_percentage' => 100,
            'status' => 'ACTIVE',
            'partnership_start_date' => now(),
        ]);
    }

    /** @test */
    public function test_roles_and_permissions_are_properly_defined()
    {
        // Check all expected roles exist
        $this->assertTrue(Role::where('name', 'super_admin')->exists());
        $this->assertTrue(Role::where('name', 'company_owner')->exists());
        $this->assertTrue(Role::where('name', 'partner')->exists());
        $this->assertTrue(Role::where('name', 'sales_rep')->exists());

        // Check super_admin has all permissions
        $superAdminRole = Role::findByName('super_admin');
        $this->assertGreaterThan(0, $superAdminRole->permissions->count());

        // Check company_owner has appropriate permissions
        $companyOwnerRole = Role::findByName('company_owner');
        $this->assertTrue($companyOwnerRole->hasPermissionTo('manage_company'));
        $this->assertTrue($companyOwnerRole->hasPermissionTo('view_all_stores'));
        $this->assertTrue($companyOwnerRole->hasPermissionTo('manage_transactions'));

        // Check partner has limited permissions
        $partnerRole = Role::findByName('partner');
        $this->assertTrue($partnerRole->hasPermissionTo('view_own_stores'));
        $this->assertTrue($partnerRole->hasPermissionTo('view_own_profit_shares'));
        $this->assertFalse($partnerRole->hasPermissionTo('manage_company'));
        $this->assertFalse($partnerRole->hasPermissionTo('delete_stores'));

        // Check sales_rep has specific permissions
        $salesRepRole = Role::findByName('sales_rep');
        $this->assertTrue($salesRepRole->hasPermissionTo('create_manual_orders'));
        $this->assertTrue($salesRepRole->hasPermissionTo('view_own_orders'));
        $this->assertFalse($salesRepRole->hasPermissionTo('manage_transactions'));
    }

    /** @test */
    public function test_company_isolation_works_correctly()
    {
        // Company Owner 1 should only see their company's stores
        $this->actingAs($this->companyOwner1);
        $visibleStores = Store::all();
        $this->assertCount(2, $visibleStores);
        $this->assertTrue($visibleStores->contains($this->store1));
        $this->assertTrue($visibleStores->contains($this->store2));
        $this->assertFalse($visibleStores->contains($this->store3));

        // Company Owner 2 should only see their company's stores
        $this->actingAs($this->companyOwner2);
        $visibleStores = Store::all();
        $this->assertCount(1, $visibleStores);
        $this->assertTrue($visibleStores->contains($this->store3));
        $this->assertFalse($visibleStores->contains($this->store1));
    }

    /** @test */
    public function test_partner_store_visibility_restrictions()
    {
        // Partner 1 should only see Store 1
        $this->actingAs($this->partner1);
        $this->assertTrue($this->partner1->hasStoreAccess($this->store1->id));
        $this->assertFalse($this->partner1->hasStoreAccess($this->store2->id));
        $this->assertFalse($this->partner1->hasStoreAccess($this->store3->id));

        // Partner 2 should see both Store 1 and Store 2
        $this->actingAs($this->partner2);
        $this->assertTrue($this->partner2->hasStoreAccess($this->store1->id));
        $this->assertTrue($this->partner2->hasStoreAccess($this->store2->id));
        $this->assertFalse($this->partner2->hasStoreAccess($this->store3->id));

        // Check accessible store IDs
        $partner1Stores = $this->partner1->getAccessibleStoreIds();
        $this->assertEquals([$this->store1->id], $partner1Stores);

        $partner2Stores = $this->partner2->getAccessibleStoreIds();
        $this->assertCount(2, $partner2Stores);
        $this->assertContains($this->store1->id, $partner2Stores);
        $this->assertContains($this->store2->id, $partner2Stores);
    }

    /** @test */
    public function test_sales_rep_limitations()
    {
        $this->actingAs($this->salesRep);

        // Sales rep should have limited permissions
        $this->assertTrue($this->salesRep->can('create_manual_orders'));
        $this->assertTrue($this->salesRep->can('view_own_orders'));
        $this->assertTrue($this->salesRep->can('manage_customers'));
        
        // Sales rep should NOT have these permissions
        $this->assertFalse($this->salesRep->can('manage_company'));
        $this->assertFalse($this->salesRep->can('delete_stores'));
        $this->assertFalse($this->salesRep->can('manage_transactions'));
        $this->assertFalse($this->salesRep->can('manage_bank_accounts'));
    }

    /** @test */
    public function test_store_policy_authorization()
    {
        // Create a transaction for testing
        $transaction = Transaction::factory()->create([
            'store_id' => $this->store1->id,
        ]);

        // Company Owner 1 can view/edit their stores
        $this->actingAs($this->companyOwner1);
        $this->assertTrue($this->companyOwner1->can('view', $this->store1));
        $this->assertTrue($this->companyOwner1->can('update', $this->store1));
        $this->assertTrue($this->companyOwner1->can('delete', $this->store1));
        $this->assertFalse($this->companyOwner1->can('view', $this->store3)); // Different company

        // Partner 1 can only view their stores
        $this->actingAs($this->partner1);
        $this->assertTrue($this->partner1->can('view', $this->store1));
        $this->assertFalse($this->partner1->can('update', $this->store1));
        $this->assertFalse($this->partner1->can('delete', $this->store1));
        $this->assertFalse($this->partner1->can('view', $this->store2)); // No partnership

        // Partner 2 can view both stores they have partnerships in
        $this->actingAs($this->partner2);
        $this->assertTrue($this->partner2->can('view', $this->store1));
        $this->assertTrue($this->partner2->can('view', $this->store2));
        $this->assertFalse($this->partner2->can('update', $this->store1));
    }

    /** @test */
    public function test_transaction_policy_authorization()
    {
        // Create transactions with proper user
        $transaction1 = Transaction::factory()->create([
            'store_id' => $this->store1->id,
            'created_by' => $this->companyOwner1->id,
        ]);
        $transaction2 = Transaction::factory()->create([
            'store_id' => $this->store2->id,
            'created_by' => $this->companyOwner1->id,
        ]);
        $transaction3 = Transaction::factory()->create([
            'store_id' => $this->store3->id,
            'created_by' => $this->companyOwner2->id,
        ]);

        // Company Owner 1 can manage their company's transactions
        $this->actingAs($this->companyOwner1);
        $this->assertTrue($this->companyOwner1->can('view', $transaction1));
        $this->assertTrue($this->companyOwner1->can('update', $transaction1));
        $this->assertTrue($this->companyOwner1->can('delete', $transaction1));
        $this->assertFalse($this->companyOwner1->can('view', $transaction3)); // Different company

        // Partner 1 can only view transactions from their stores
        $this->actingAs($this->partner1);
        $this->assertTrue($this->partner1->can('view', $transaction1));
        $this->assertFalse($this->partner1->can('update', $transaction1));
        $this->assertFalse($this->partner1->can('view', $transaction2)); // No access to store2
    }

    /** @test */
    public function test_partnership_policy_authorization()
    {
        $partnership1 = Partnership::where('store_id', $this->store1->id)
            ->where('user_id', $this->partner1->id)
            ->first();

        // Company Owner can manage partnerships
        $this->actingAs($this->companyOwner1);
        $this->assertTrue($this->companyOwner1->can('view', $partnership1));
        $this->assertTrue($this->companyOwner1->can('update', $partnership1));
        $this->assertTrue($this->companyOwner1->can('delete', $partnership1));

        // Partner can only view their own partnerships
        $this->actingAs($this->partner1);
        $this->assertTrue($this->partner1->can('view', $partnership1));
        $this->assertFalse($this->partner1->can('update', $partnership1));
        $this->assertFalse($this->partner1->can('delete', $partnership1));

        // Other partners cannot view partnerships they're not part of
        $partnership2 = Partnership::where('store_id', $this->store2->id)
            ->where('user_id', $this->partner2->id)
            ->first();
        $this->assertFalse($this->partner1->can('view', $partnership2));
    }

    /** @test */
    public function test_super_admin_override_issue()
    {
        $this->actingAs($this->superAdmin);

        // Super admin should be able to bypass all company restrictions
        $this->assertTrue($this->superAdmin->can('view', $this->store1)); // Company 1
        $this->assertTrue($this->superAdmin->can('view', $this->store3)); // Company 2
        $this->assertTrue($this->superAdmin->can('update', $this->store1));
        $this->assertTrue($this->superAdmin->can('delete', $this->store3));

        // Super admin should see all stores via global scope
        $allStores = Store::all();
        $this->assertGreaterThanOrEqual(3, $allStores->count());
        $this->assertTrue($allStores->contains($this->store1));
        $this->assertTrue($allStores->contains($this->store2));
        $this->assertTrue($allStores->contains($this->store3));

        // Super admin can manage everything
        $transaction = Transaction::factory()->create([
            'store_id' => $this->store3->id,
            'created_by' => $this->companyOwner2->id,
        ]);
        $this->assertTrue($this->superAdmin->can('view', $transaction));
        $this->assertTrue($this->superAdmin->can('update', $transaction));
        $this->assertTrue($this->superAdmin->can('delete', $transaction));
    }

    /** @test */
    public function test_global_scopes_with_super_admin()
    {
        $this->actingAs($this->superAdmin);

        // Super admin should see all data across all companies
        $stores = Store::all();
        $this->assertGreaterThanOrEqual(3, $stores->count());

        $transactions = Transaction::all();
        $this->assertGreaterThanOrEqual(0, $transactions->count());

        $bankAccounts = BankAccount::all();
        $this->assertGreaterThanOrEqual(0, $bankAccounts->count());

        $paymentProcessors = PaymentProcessorAccount::all();
        $this->assertGreaterThanOrEqual(0, $paymentProcessors->count());

        $importBatches = ImportBatch::all();
        $this->assertGreaterThanOrEqual(0, $importBatches->count());

        // Super admin can create resources in any company
        $newStore = Store::create([
            'company_id' => $this->company2->id,
            'name' => 'Super Admin Created Store',
            'currency' => 'USD',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('stores', ['id' => $newStore->id]);
    }

    /** @test */
    public function test_missing_policies()
    {
        // Check for missing policies on important models
        $modelsNeedingPolicies = [
            BankAccount::class => 'BankAccountPolicy',
            PaymentProcessorAccount::class => 'PaymentProcessorAccountPolicy',
            Company::class => 'CompanyPolicy',
        ];

        foreach ($modelsNeedingPolicies as $model => $expectedPolicy) {
            $policyClass = 'App\\Policies\\' . $expectedPolicy;
            $this->assertTrue(
                class_exists($policyClass),
                "Policy {$expectedPolicy} should exist for model {$model}"
            );
        }
    }

    /** @test */
    public function test_user_type_vs_role_consistency()
    {
        // Check that user_type field matches assigned roles
        $this->assertEquals('company_owner', $this->companyOwner1->user_type);
        $this->assertTrue($this->companyOwner1->hasRole('company_owner'));

        $this->assertEquals('partner', $this->partner1->user_type);
        $this->assertTrue($this->partner1->hasRole('partner'));

        // Sales rep has 'viewer' user_type but 'sales_rep' role
        $this->assertEquals('viewer', $this->salesRep->user_type);
        $this->assertTrue($this->salesRep->hasRole('sales_rep'));
    }

    /** @test */
    public function test_permission_name_consistency()
    {
        // Check for inconsistent permission naming
        $permissions = Permission::pluck('name')->toArray();
        
        // Some permissions use 'view_own_*' pattern
        $this->assertContains('view_own_stores', $permissions);
        $this->assertContains('view_own_orders', $permissions);
        
        // Others use 'view_all_*' pattern
        $this->assertContains('view_all_stores', $permissions);
        $this->assertContains('view_all_orders', $permissions);
        
        // Check for potential missing permissions
        $expectedPermissions = [
            'manage_imports',
            'manage_exports',
            'view_bank_accounts',
            'edit_bank_accounts',
        ];
        
        foreach ($expectedPermissions as $permission) {
            $this->assertContains(
                $permission,
                $permissions,
                "Expected permission '{$permission}' not found"
            );
        }
    }
}