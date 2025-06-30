<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsAndRolesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Company owner permissions
            'manage_company',
            'invite_partners',
            'view_all_stores',
            'view_all_profit_shares',

            // Partner permissions
            'view_own_stores',
            'view_own_profit_shares',
            'manage_personal_expenses',

            // Store management
            'create_stores',
            'edit_stores',
            'delete_stores',
            'view_store_analytics',

            // Financial management
            'manage_transactions',
            'view_financial_reports',
            'manage_bank_accounts',
            'manage_payment_processors',

            // Partnership management
            'create_partnerships',
            'edit_partnerships',
            'delete_partnerships',
            'view_partnership_details',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Company Owner Role
        $companyOwnerRole = Role::create(['name' => 'company_owner']);
        $companyOwnerRole->givePermissionTo([
            'manage_company',
            'invite_partners',
            'view_all_stores',
            'view_all_profit_shares',
            'create_stores',
            'edit_stores',
            'delete_stores',
            'view_store_analytics',
            'manage_transactions',
            'view_financial_reports',
            'manage_bank_accounts',
            'manage_payment_processors',
            'create_partnerships',
            'edit_partnerships',
            'delete_partnerships',
            'view_partnership_details',
        ]);

        // Partner Role
        $partnerRole = Role::create(['name' => 'partner']);
        $partnerRole->givePermissionTo([
            'view_own_stores',
            'view_own_profit_shares',
            'manage_personal_expenses',
            'view_store_analytics',
            'view_partnership_details',
        ]);

        // Staff Role (for future use)
        $staffRole = Role::create(['name' => 'staff']);
        $staffRole->givePermissionTo([
            'view_store_analytics',
            'manage_transactions',
        ]);

        $this->command->info('Permissions and roles created successfully!');
    }
}
