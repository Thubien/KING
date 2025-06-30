<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Partnership;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
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

        // Create partnerships
        $partnership1 = Partnership::create([
            'partner_id' => $partner->id,
            'partner_percentage' => 30.0,
            'sales_rep_percentage' => 10.0,
            'company_percentage' => 60.0,
            'status' => 'active',
        ]);

        $partnership1->stores()->attach([$shopifyStore->id, $instagramStore->id]);

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
                Transaction::create([
                    'company_id' => $company->id,
                    'store_id' => $shopifyStore->id,
                    'external_id' => 'shopify_'.$date->format('Ym').'_'.$i,
                    'type' => 'sale',
                    'amount_original' => $amount,
                    'currency_original' => 'USD',
                    'amount_usd' => $amount,
                    'transaction_date' => $date->copy()->addDays(rand(1, 28)),
                    'description' => 'Shopify Order #'.rand(1000, 9999),
                    'sales_channel' => 'shopify',
                    'payment_method' => collect(['credit_card', 'bank_transfer', 'cash'])->random(),
                    'data_source' => 'shopify_api',
                    'status' => 'completed',
                    'sales_rep_id' => $salesRep->id,
                    'customer_info' => [
                        'email' => 'customer'.$i.'@example.com',
                        'first_name' => 'Customer',
                        'last_name' => 'Demo',
                    ],
                    'metadata' => [
                        'shopify_order_id' => rand(100000, 999999),
                        'synced_at' => now()->toISOString(),
                    ],
                ]);
            }

            // Instagram transactions
            for ($i = 0; $i < rand(3, 10); $i++) {
                $amount = rand(15, 200);
                Transaction::create([
                    'company_id' => $company->id,
                    'store_id' => $instagramStore->id,
                    'type' => 'sale',
                    'amount_original' => $amount,
                    'currency_original' => 'USD',
                    'amount_usd' => $amount,
                    'transaction_date' => $date->copy()->addDays(rand(1, 28)),
                    'description' => 'Instagram Order #IG'.rand(100, 999),
                    'sales_channel' => 'instagram',
                    'payment_method' => collect(['cash', 'cash_on_delivery', 'bank_transfer'])->random(),
                    'data_source' => 'manual_entry',
                    'status' => 'completed',
                    'sales_rep_id' => $salesRep->id,
                    'customer_info' => [
                        'instagram_handle' => '@customer'.$i,
                        'phone' => '+1234567890',
                    ],
                    'created_by' => $salesRep->id,
                ]);
            }
        }
    }
}
