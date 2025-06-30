<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Partnership;
use App\Models\PaymentProcessorAccount;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions if they don't exist
        $permissions = [
            'view_all_companies',
            'manage_all_companies',
            'view_all_transactions',
            'manage_all_transactions',
            'view_all_users',
            'manage_all_users',
            'access_admin_panel',
            'manage_system_settings',
            'view_analytics',
            'manage_imports',
            'manage_exports',
            'manage_partnerships',
            'manage_bank_accounts',
            'manage_payment_processors',
            'view_all_reports',
            'bypass_limits',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create super admin role
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->syncPermissions(Permission::all());

        // Create premium company for super admin
        $company = Company::firstOrCreate(
            ['domain' => 'superadmin.king.com'],
            [
                'name' => 'Super Admin Company',
                'slug' => 'super-admin',
                'description' => 'Super Admin full access company',
                'timezone' => 'UTC',
                'currency' => 'USD',
                'status' => 'active',
                'plan' => 'enterprise',
                'is_trial' => false,
                'plan_expires_at' => now()->addYears(100),
                // Enable all premium features
                'api_integrations_enabled' => true,
                'webhooks_enabled' => true,
                'real_time_sync_enabled' => true,
                'max_api_calls_per_month' => 999999,
                'settings' => [
                    'multi_currency_enabled' => true,
                    'advanced_analytics_enabled' => true,
                    'ai_categorization_enabled' => true,
                    'bulk_operations_enabled' => true,
                    'custom_categories_enabled' => true,
                    'unlimited_stores' => true,
                    'unlimited_users' => true,
                ],
            ]
        );

        // Create super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'super@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin123'),
                'company_id' => $company->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Assign super admin role
        $superAdmin->assignRole($superAdminRole);

        // Create demo stores for super admin
        $store1 = Store::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Demo Fashion Store',
            ],
            [
                'shopify_domain' => 'demo-fashion.myshopify.com',
                'currency' => 'USD',
                'country_code' => 'US',
                'timezone' => 'America/New_York',
                'status' => 'active',
            ]
        );

        $store2 = Store::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Demo Electronics Store',
            ],
            [
                'shopify_domain' => 'demo-electronics.myshopify.com',
                'currency' => 'EUR',
                'country_code' => 'DE',
                'timezone' => 'Europe/Berlin',
                'status' => 'active',
            ]
        );

        // Create partnerships
        Partnership::firstOrCreate([
            'store_id' => $store1->id,
            'user_id' => $superAdmin->id,
        ], [
            'ownership_percentage' => 100,
            'role' => 'owner',
            'status' => 'ACTIVE',
            'partnership_start_date' => now(),
        ]);

        Partnership::firstOrCreate([
            'store_id' => $store2->id,
            'user_id' => $superAdmin->id,
        ], [
            'ownership_percentage' => 100,
            'role' => 'owner',
            'status' => 'ACTIVE',
            'partnership_start_date' => now(),
        ]);

        // Create bank accounts
        BankAccount::firstOrCreate([
            'company_id' => $company->id,
            'bank_name' => 'Mercury Bank',
            'account_name' => 'Super Admin Main Account',
        ], [
            'bank_type' => 'business',
            'currency' => 'USD',
            'current_balance' => 1000000,
            'is_active' => true,
        ]);

        BankAccount::firstOrCreate([
            'company_id' => $company->id,
            'bank_name' => 'Payoneer',
            'account_name' => 'Super Admin EUR Account',
        ], [
            'bank_type' => 'business',
            'currency' => 'EUR',
            'current_balance' => 500000,
            'is_active' => true,
        ]);

        // Create payment processor accounts
        PaymentProcessorAccount::firstOrCreate([
            'company_id' => $company->id,
            'processor_type' => 'STRIPE',
        ], [
            'currency' => 'USD',
            'current_balance' => 50000,
            'pending_balance' => 25000,
            'is_active' => true,
        ]);

        PaymentProcessorAccount::firstOrCreate([
            'company_id' => $company->id,
            'processor_type' => 'PAYPAL',
        ], [
            'currency' => 'USD',
            'current_balance' => 30000,
            'pending_balance' => 15000,
            'is_active' => true,
        ]);

        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: super@admin.com');
        $this->command->info('Password: superadmin123');
        $this->command->info('Company: Super Admin Company');
        $this->command->info('Plan: Enterprise (All features enabled)');
    }
}
