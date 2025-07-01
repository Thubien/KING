<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Partnership;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing demo data
        $existingCompany = Company::where('name', 'EcomBoard Demo Company')->first();
        if ($existingCompany) {
            $existingCompany->forceDelete();
        }
        User::whereIn('email', ['owner@demo.com', 'salesrep@demo.com', 'partner@demo.com'])->forceDelete();
        // Create demo company
        $company = Company::create([
            'name' => 'EcomBoard Demo Company',
            'plan' => 'professional',
            'status' => 'active',
        ]);

        // Create company owner
        $owner = User::create([
            'name' => 'Demo Owner',
            'email' => 'owner@demo.com',
            'password' => Hash::make('password'),
            'company_id' => $company->id,
        ]);

        $ownerRole = Role::where('name', 'company_owner')->first();
        $owner->assignRole($ownerRole);

        // Create sales rep
        $salesRep = User::create([
            'name' => 'Sales Rep Demo',
            'email' => 'salesrep@demo.com',
            'password' => Hash::make('password'),
            'company_id' => $company->id,
        ]);

        $salesRepRole = Role::where('name', 'sales_rep')->first();
        $salesRep->assignRole($salesRepRole);

        // Create partner
        $partner = User::create([
            'name' => 'Partner Demo',
            'email' => 'partner@demo.com',
            'password' => Hash::make('password'),
            'company_id' => $company->id,
        ]);

        $partnerRole = Role::where('name', 'partner')->first();
        $partner->assignRole($partnerRole);

        // Create demo stores
        $shopifyStore = Store::create([
            'company_id' => $company->id,
            'name' => 'Demo Shopify Store',
            'shopify_domain' => 'demo-store.myshopify.com',
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $instagramStore = Store::create([
            'company_id' => $company->id,
            'name' => 'Instagram Boutique',
            'shopify_domain' => null,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        // Create partnerships for each store
        Partnership::create([
            'store_id' => $shopifyStore->id,
            'user_id' => $owner->id,
            'ownership_percentage' => 60.0,
            'role' => 'owner',
            'status' => 'ACTIVE',
            'partnership_start_date' => now()->subMonths(6),
        ]);

        Partnership::create([
            'store_id' => $shopifyStore->id,
            'user_id' => $partner->id,
            'ownership_percentage' => 30.0,
            'role' => 'partner',
            'status' => 'ACTIVE',
            'partnership_start_date' => now()->subMonths(6),
        ]);

        Partnership::create([
            'store_id' => $shopifyStore->id,
            'user_id' => $salesRep->id,
            'ownership_percentage' => 10.0,
            'role' => 'partner',
            'status' => 'ACTIVE',
            'partnership_start_date' => now()->subMonths(6),
        ]);

        // Instagram store partnerships
        Partnership::create([
            'store_id' => $instagramStore->id,
            'user_id' => $owner->id,
            'ownership_percentage' => 70.0,
            'role' => 'owner',
            'status' => 'ACTIVE',
            'partnership_start_date' => now()->subMonths(5),
        ]);

        Partnership::create([
            'store_id' => $instagramStore->id,
            'user_id' => $partner->id,
            'ownership_percentage' => 30.0,
            'role' => 'partner',
            'status' => 'ACTIVE',
            'partnership_start_date' => now()->subMonths(5),
        ]);

        // Create demo transactions
        $this->createDemoTransactions($company, $shopifyStore, $instagramStore, $salesRep);

        $this->command->info('Demo data created successfully!');
        $this->command->info('Company Owner: owner@demo.com / password');
        $this->command->info('Sales Rep: salesrep@demo.com / password');
        $this->command->info('Partner: partner@demo.com / password');
    }

    private function createDemoTransactions(Company $company, Store $shopifyStore, Store $instagramStore, User $salesRep): void
    {
        // Last 6 months of transactions
        for ($month = 5; $month >= 0; $month--) {
            $date = now()->subMonths($month);

            // Shopify transactions
            for ($i = 0; $i < rand(5, 15); $i++) {
                $amount = rand(25, 500);
                
                // Create or find customer for this transaction
                $customerEmail = 'customer'.$i.'@example.com';
                $customer = Customer::where('store_id', $shopifyStore->id)
                    ->where('email', $customerEmail)
                    ->first();
                    
                if (!$customer) {
                    $customer = Customer::create([
                        'company_id' => $company->id,
                        'store_id' => $shopifyStore->id,
                        'name' => 'Demo Customer ' . $i,
                        'email' => $customerEmail,
                        'phone' => '+1555' . str_pad($i, 7, '0', STR_PAD_LEFT),
                        'source' => 'shopify',
                        'status' => 'active',
                        'accepts_marketing' => rand(0, 1) == 1,
                    ]);
                }
                
                $transaction = Transaction::create([
                    'store_id' => $shopifyStore->id,
                    'customer_id' => $customer->id,
                    'transaction_id' => 'SHOP-' . $date->format('Ymd') . '-' . rand(1000, 9999),
                    'amount' => $amount,
                    'currency' => 'USD',
                    'amount_usd' => $amount,
                    'category' => 'SALES',
                    'type' => 'income',
                    'status' => 'APPROVED',
                    'transaction_date' => $date->copy()->addDays(rand(1, 28)),
                    'description' => 'Shopify Order #'.rand(1000, 9999),
                    'sales_channel' => 'shopify',
                    'payment_method' => collect(['credit_card', 'bank_transfer', 'cash'])->random(),
                    'data_source' => 'shopify_api',
                    'sales_rep_id' => $salesRep->id,
                    'customer_info' => [
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                    ],
                    'metadata' => [
                        'shopify_order_id' => rand(100000, 999999),
                        'synced_at' => now()->toISOString(),
                    ],
                    'created_by' => $salesRep->id,
                ]);
                
                // Update customer statistics
                $customer->updateStatistics();
            }

            // Instagram transactions
            for ($i = 0; $i < rand(3, 10); $i++) {
                $amount = rand(15, 200);
                
                // Create or find customer for Instagram
                $customerPhone = '+90555' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
                $customer = Customer::where('store_id', $instagramStore->id)
                    ->where('phone', $customerPhone)
                    ->first();
                    
                if (!$customer) {
                    $customer = Customer::create([
                        'company_id' => $company->id,
                        'store_id' => $instagramStore->id,
                        'name' => 'Instagram Müşteri ' . $i,
                        'phone' => $customerPhone,
                        'whatsapp_number' => str_replace('+', '', $customerPhone),
                        'source' => 'manual',
                        'status' => 'active',
                        'accepts_marketing' => true,
                        'preferred_contact_method' => 'whatsapp',
                    ]);
                }
                
                $transaction = Transaction::create([
                    'store_id' => $instagramStore->id,
                    'customer_id' => $customer->id,
                    'transaction_id' => 'IG-' . $date->format('Ymd') . '-' . rand(1000, 9999),
                    'amount' => $amount,
                    'currency' => 'USD',
                    'amount_usd' => $amount,
                    'category' => 'SALES',
                    'type' => 'income',
                    'status' => 'APPROVED',
                    'transaction_date' => $date->copy()->addDays(rand(1, 28)),
                    'description' => 'Instagram Order #IG'.rand(100, 999),
                    'sales_channel' => 'instagram',
                    'payment_method' => collect(['cash', 'cash_on_delivery', 'bank_transfer'])->random(),
                    'data_source' => 'manual_entry',
                    'sales_rep_id' => $salesRep->id,
                    'customer_info' => [
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'instagram_handle' => '@customer'.$i,
                    ],
                    'created_by' => $salesRep->id,
                ]);
                
                // Update customer statistics
                $customer->updateStatistics();
            }
        }
    }
}
