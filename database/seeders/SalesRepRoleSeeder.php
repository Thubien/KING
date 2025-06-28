<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SalesRepRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create Sales Rep role if it doesn't exist
        $salesRepRole = Role::firstOrCreate(['name' => 'sales_rep']);
        
        // Define Sales Rep permissions
        $salesRepPermissions = [
            // Manual Orders - Can create and view their own orders
            'create_manual_orders',
            'view_own_orders',
            'edit_own_orders',
            
            // Customer Management
            'manage_customers',
            'view_customer_database',
            
            // Commission & Analytics
            'view_own_commission',
            'view_own_analytics',
            'export_own_data',
            
            // Profile Management
            'update_own_profile',
        ];
        
        // Create permissions if they don't exist
        foreach ($salesRepPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        // Assign permissions to Sales Rep role
        $salesRepRole->syncPermissions($salesRepPermissions);
        
        $this->command->info('Sales Rep role and permissions created successfully!');
        
        // Update existing roles to include manual order permissions
        $this->updateExistingRoles();
    }
    
    private function updateExistingRoles(): void
    {
        // Company Owner permissions (can do everything)
        $companyOwnerRole = Role::where('name', 'company_owner')->first();
        if ($companyOwnerRole) {
            $ownerPermissions = [
                'create_manual_orders',
                'view_all_orders',
                'edit_all_orders',
                'delete_orders',
                'manage_customers',
                'view_customer_database',
                'view_all_commission',
                'view_all_analytics',
                'manage_sales_reps',
                'export_all_data',
            ];
            
            foreach ($ownerPermissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
            
            $companyOwnerRole->givePermissionTo($ownerPermissions);
        }
        
        // Admin permissions (similar to company owner)
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminPermissions = [
                'create_manual_orders',
                'view_all_orders',
                'edit_all_orders',
                'manage_customers',
                'view_customer_database',
                'view_all_commission',
                'view_all_analytics',
                'export_all_data',
            ];
            
            foreach ($adminPermissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
            
            $adminRole->givePermissionTo($adminPermissions);
        }
        
        // Partner permissions (view only their store's data)
        $partnerRole = Role::where('name', 'partner')->first();
        if ($partnerRole) {
            $partnerPermissions = [
                'view_own_store_orders',
                'view_own_store_analytics',
                'view_customer_database',
                'export_own_store_data',
            ];
            
            foreach ($partnerPermissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
            
            $partnerRole->givePermissionTo($partnerPermissions);
        }
        
        $this->command->info('Existing roles updated with manual order permissions!');
    }
}