<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Partnership;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TransactionTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Find or create test company
        $company = Company::firstOrCreate(
            ['domain' => 'test-ecommerce.com'],
            [
                'name' => 'Test E-commerce Company',
                'description' => 'Test company for transaction editor',
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'status' => 'active',
                'plan' => 'professional',
                'is_trial' => false,
                'api_integrations_enabled' => true,
                'webhooks_enabled' => true,
                'real_time_sync_enabled' => true,
                'max_api_calls_per_month' => 10000,
            ]
        );

        // Find or create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
            ]
        );

        // Find or create partners
        $partner1 = User::firstOrCreate(
            ['email' => 'john@test.com'],
            [
                'name' => 'John Partner',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
            ]
        );

        $partner2 = User::firstOrCreate(
            ['email' => 'jane@test.com'],
            [
                'name' => 'Jane Partner',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
            ]
        );

        // Find or create stores
        $store1 = Store::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Fashion Store',
            ],
            [
                'currency' => 'USD',
                'country_code' => 'US',
                'timezone' => 'America/New_York',
                'status' => 'active',
            ]
        );

        $store2 = Store::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Electronics Store',
            ],
            [
                'currency' => 'USD',
                'country_code' => 'US',
                'timezone' => 'America/New_York',
                'status' => 'active',
            ]
        );

        // Find or create partnerships
        Partnership::firstOrCreate([
            'store_id' => $store1->id,
            'user_id' => $partner1->id,
        ], [
            'ownership_percentage' => 60,
            'role' => 'managing_partner',
            'status' => 'ACTIVE',
            'partnership_start_date' => now(),
        ]);

        Partnership::firstOrCreate([
            'store_id' => $store1->id,
            'user_id' => $partner2->id,
        ], [
            'ownership_percentage' => 40,
            'role' => 'partner',
            'status' => 'ACTIVE',
            'partnership_start_date' => now(),
        ]);

        Partnership::firstOrCreate([
            'store_id' => $store2->id,
            'user_id' => $partner1->id,
        ], [
            'ownership_percentage' => 50,
            'role' => 'partner',
            'status' => 'ACTIVE',
            'partnership_start_date' => now(),
        ]);

        Partnership::firstOrCreate([
            'store_id' => $store2->id,
            'user_id' => $partner2->id,
        ], [
            'ownership_percentage' => 50,
            'role' => 'partner',
            'status' => 'ACTIVE',
            'partnership_start_date' => now(),
        ]);

        // Find or create bank account
        $bankAccount = BankAccount::firstOrCreate([
            'company_id' => $company->id,
            'bank_name' => 'Mercury Bank',
        ], [
            'bank_type' => 'business',
            'account_name' => 'Main Business Account',
            'currency' => 'USD',
            'current_balance' => 50000,
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Create test transactions
        $this->createTestTransactions($company, [$store1, $store2], $bankAccount);
    }

    private function createTestTransactions($company, $stores, $bankAccount): void
    {
        // Get the first user as creator
        $creator = User::where('company_id', $company->id)->first();

        $descriptions = [
            // Income
            ['Shopify Payment - Order #1234', 'INCOME', 1250.00],
            ['Shopify Payment - Order #1235', 'INCOME', 890.50],
            ['Stripe Payout - June 2024', 'INCOME', 5420.00],
            ['Customer Payment - Invoice #456', 'INCOME', 2100.00],

            // Expenses - Ads
            ['Facebook Ads Campaign - Summer Sale', 'EXPENSE', -450.00],
            ['Meta Business - Ad Spend June', 'EXPENSE', -320.00],
            ['Google Ads - Search Campaign', 'EXPENSE', -280.00],
            ['TikTok Ads - Product Launch', 'EXPENSE', -190.00],

            // Expenses - Suppliers
            ['Alibaba - Product Order #789', 'EXPENSE', -3200.00],
            ['Supplier Payment - Fashion Vendor', 'EXPENSE', -1800.00],
            ['DHL Express Shipping', 'EXPENSE', -150.00],
            ['FedEx International Delivery', 'EXPENSE', -220.00],

            // Bank Fees
            ['Payoneer Transfer Fee', 'EXPENSE', -25.00],
            ['Mercury Monthly Fee', 'EXPENSE', -20.00],
            ['International Wire Fee', 'EXPENSE', -45.00],
            ['Currency Exchange Fee USD-EUR', 'EXPENSE', -32.50],

            // Transfers
            ['Transfer to Payoneer EUR Account', 'EXPENSE', -1000.00],
            ['Transfer from USD Account', 'INCOME', 920.00], // EUR equivalent

            // Other
            ['Office Rent - June 2024', 'EXPENSE', -1200.00],
            ['Employee Salary - Marketing', 'EXPENSE', -2500.00],
            ['Software Subscription - Shopify Plus', 'EXPENSE', -299.00],
            ['Partner Withdrawal - John', 'EXPENSE', -1500.00],
        ];

        foreach ($descriptions as $index => $item) {
            $date = Carbon::now()->subDays(rand(1, 30));

            Transaction::create([
                'amount' => $item[2],
                'currency' => 'USD',
                'type' => $item[1],
                'description' => $item[0],
                'transaction_date' => $date,
                'status' => 'completed',
                'assignment_status' => 'pending',
                'source' => 'csv_import',
                'created_by' => $creator->id,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        // Create some assigned transactions for smart suggestions
        $assignedTransactions = [
            ['Facebook Ads - Campaign Q1', 'EXPENSE', -300.00, $stores[0]->id, 'ADS', 'FACEBOOK'],
            ['Meta Ads Manager - Budget', 'EXPENSE', -250.00, $stores[0]->id, 'ADS', 'FACEBOOK'],
            ['Stripe Fee - Transaction Fee', 'EXPENSE', -45.00, $stores[0]->id, 'FEE', null],
            ['Payoneer Transfer Fee', 'EXPENSE', -30.00, null, 'BANK_FEE', 'TRANSFER_FEE'],
        ];

        foreach ($assignedTransactions as $item) {
            Transaction::create([
                'store_id' => $item[3],
                'amount' => $item[2],
                'currency' => 'USD',
                'type' => $item[1],
                'category' => $item[4],
                'subcategory' => $item[5],
                'description' => $item[0],
                'transaction_date' => Carbon::now()->subDays(rand(31, 60)),
                'status' => 'completed',
                'assignment_status' => 'assigned',
                'source' => 'csv_import',
                'created_by' => $creator->id,
            ]);
        }
    }
}
