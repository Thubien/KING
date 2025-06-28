<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use App\Models\Transaction;

class ManualOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_instagram_manual_order()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $salesRep = User::factory()->create(['company_id' => $company->id]);

        $orderData = [
            'store_id' => $store->id,
            'created_by' => $salesRep->id,
            'sales_channel' => 'instagram',
            'payment_method' => 'bank_transfer',
            'data_source' => 'manual_entry',
            'description' => 'Instagram DM sale - White dress',
            'amount' => 150.00,
            'currency' => 'TRY',
            'type' => 'INCOME',
            'category' => 'SALES',
            'status' => 'APPROVED',
            'transaction_date' => now(),
            'customer_info' => [
                'name' => 'Ayşe Yılmaz',
                'phone' => '+90532xxxxxxx',
                'instagram_handle' => 'ayse_style',
                'address' => 'Kadıköy, İstanbul'
            ],
            'sales_rep_id' => $salesRep->id,
            'order_notes' => 'Customer saw product in story, requested via DM',
            'order_reference' => 'https://instagram.com/p/xyz123'
        ];

        $transaction = Transaction::create($orderData);

        $this->assertDatabaseHas('transactions', [
            'sales_channel' => 'instagram',
            'payment_method' => 'bank_transfer',
            'data_source' => 'manual_entry',
            'sales_rep_id' => $salesRep->id
        ]);

        $this->assertEquals('Ayşe Yılmaz', $transaction->customer_info['name']);
        $this->assertEquals('ayse_style', $transaction->customer_info['instagram_handle']);
        $this->assertEquals($salesRep->id, $transaction->sales_rep_id);
    }

    public function test_can_create_telegram_manual_order()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $salesRep = User::factory()->create(['company_id' => $company->id]);

        $orderData = [
            'store_id' => $store->id,
            'created_by' => $salesRep->id,
            'sales_channel' => 'telegram',
            'payment_method' => 'crypto',
            'data_source' => 'manual_entry',
            'description' => 'Premium headphones',
            'amount' => 500.00,
            'currency' => 'USD',
            'type' => 'INCOME',
            'category' => 'SALES',
            'status' => 'APPROVED',
            'transaction_date' => now(),
            'customer_info' => [
                'name' => 'Mehmet K.',
                'telegram_handle' => 'mehmet_crypto',
                'phone' => '+90555xxxxxxx'
            ],
            'sales_rep_id' => $salesRep->id,
            'order_notes' => 'Came from Telegram channel, paid with Bitcoin'
        ];

        $transaction = Transaction::create($orderData);

        $this->assertDatabaseHas('transactions', [
            'sales_channel' => 'telegram',
            'payment_method' => 'crypto',
            'data_source' => 'manual_entry'
        ]);

        $this->assertEquals('mehmet_crypto', $transaction->customer_info['telegram_handle']);
    }

    public function test_manual_order_constants_work()
    {
        $this->assertArrayHasKey('instagram', Transaction::SALES_CHANNELS);
        $this->assertArrayHasKey('telegram', Transaction::SALES_CHANNELS);
        $this->assertArrayHasKey('whatsapp', Transaction::SALES_CHANNELS);
        
        $this->assertArrayHasKey('cash', Transaction::PAYMENT_METHODS);
        $this->assertArrayHasKey('bank_transfer', Transaction::PAYMENT_METHODS);
        $this->assertArrayHasKey('cash_on_delivery', Transaction::PAYMENT_METHODS);
        $this->assertArrayHasKey('cargo_collect', Transaction::PAYMENT_METHODS);
        
        $this->assertArrayHasKey('manual_entry', Transaction::DATA_SOURCES);
        $this->assertArrayHasKey('shopify_api', Transaction::DATA_SOURCES);
    }

    public function test_sales_rep_relationship()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $salesRep = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Sales Rep Name'
        ]);

        $transaction = Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_rep_id' => $salesRep->id,
            'sales_channel' => 'instagram',
            'data_source' => 'manual_entry'
        ]);

        $this->assertEquals('Sales Rep Name', $transaction->salesRep->name);
        $this->assertEquals($salesRep->id, $transaction->sales_rep_id);
    }

    public function test_customer_info_json_casting()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        $customerInfo = [
            'name' => 'Test Customer',
            'phone' => '+90532xxxxxxx',
            'instagram_handle' => 'test_customer',
            'address' => 'Test Address, İstanbul'
        ];

        $transaction = Transaction::factory()->create([
            'store_id' => $store->id,
            'customer_info' => $customerInfo,
            'data_source' => 'manual_entry'
        ]);

        // Test JSON casting works
        $this->assertIsArray($transaction->customer_info);
        $this->assertEquals('Test Customer', $transaction->customer_info['name']);
        $this->assertEquals('test_customer', $transaction->customer_info['instagram_handle']);

        // Test from database
        $transaction->refresh();
        $this->assertIsArray($transaction->customer_info);
        $this->assertEquals('Test Customer', $transaction->customer_info['name']);
    }

    public function test_channel_analytics_queries()
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        // Create orders from different channels
        Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_channel' => 'instagram',
            'amount' => 100,
            'data_source' => 'manual_entry'
        ]);

        Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_channel' => 'telegram',
            'amount' => 200,
            'data_source' => 'manual_entry'
        ]);

        Transaction::factory()->create([
            'store_id' => $store->id,
            'sales_channel' => 'instagram',
            'amount' => 150,
            'data_source' => 'manual_entry'
        ]);

        // Test channel performance query
        $channelStats = Transaction::selectRaw('
            sales_channel,
            COUNT(*) as order_count,
            SUM(amount) as total_revenue
        ')->where('store_id', $store->id)
          ->groupBy('sales_channel')
          ->get()
          ->keyBy('sales_channel');

        $this->assertEquals(2, $channelStats['instagram']->order_count);
        $this->assertEquals(250, $channelStats['instagram']->total_revenue);
        $this->assertEquals(1, $channelStats['telegram']->order_count);
        $this->assertEquals(200, $channelStats['telegram']->total_revenue);
    }
}