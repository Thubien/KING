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

        // SIMPLIFIED: 3 Main Roles with Clear Permissions
        $permissions = [
            // Core permissions for all users
            'view_dashboard',
            'manage_profile',
            
            // Owner permissions (full company access)
            'manage_company',
            'manage_all_stores',
            'view_all_financial_data',
            'invite_users',
            'manage_partnerships',
            
            // Partner permissions (store-based access)
            'view_assigned_stores',
            'view_partnership_profits',
            'manage_personal_expenses',
            
            // Staff permissions (operational tasks)
            'create_orders',
            'manage_customers',
            'view_basic_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 1. OWNER - Company owner with full access
        $ownerRole = Role::firstOrCreate(['name' => 'owner']);
        $ownerRole->syncPermissions([
            'view_dashboard',
            'manage_profile',
            'manage_company',
            'manage_all_stores',
            'view_all_financial_data',
            'invite_users',
            'manage_partnerships',
            'view_assigned_stores',
            'view_partnership_profits',
            'manage_personal_expenses',
            'create_orders',
            'manage_customers',
            'view_basic_reports',
        ]);

        // 2. PARTNER - Store partner with limited access
        $partnerRole = Role::firstOrCreate(['name' => 'partner']);
        $partnerRole->syncPermissions([
            'view_dashboard',
            'manage_profile',
            'view_assigned_stores',
            'view_partnership_profits',
            'manage_personal_expenses',
            'view_basic_reports',
        ]);

        // 3. STAFF - Operational staff (sales rep, etc.)
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staffRole->syncPermissions([
            'view_dashboard',
            'manage_profile',
            'create_orders',
            'manage_customers',
            'view_basic_reports',
        ]);

        // 4. SUPER_ADMIN - System administrator (bypasses all restrictions)
        Role::firstOrCreate(['name' => 'super_admin']);

        $this->command->info('✅ Simplified 3-role system created successfully!');
        $this->command->info('Roles: OWNER, PARTNER, STAFF, SUPER_ADMIN');
    }
}
