<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Store;
use App\Models\Customer;
use App\Models\ReturnRequest;
use App\Models\StoreCredit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReturnRequestDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'EcomBoard Demo Company')->first();
        if (!$company) {
            $this->command->error('Demo company not found. Please run DemoDataSeeder first.');
            return;
        }

        $stores = Store::where('company_id', $company->id)->get();
        $handler = User::where('email', 'salesrep@demo.com')->first() ?? User::first();

        foreach ($stores as $store) {
            $this->createReturnRequestsForStore($store, $handler);
        }

        $this->command->info('Return request demo data created successfully!');
    }

    private function createReturnRequestsForStore(Store $store, User $handler): void
    {
        // Get some customers with orders
        $customers = Customer::where('store_id', $store->id)
            ->whereHas('transactions', function ($query) {
                $query->where('category', 'SALES')
                      ->where('type', 'income')
                      ->where('status', 'APPROVED');
            })
            ->limit(5)
            ->get();

        foreach ($customers as $customer) {
            // Get a recent transaction for this customer
            $transaction = $customer->transactions()
                ->where('category', 'SALES')
                ->where('type', 'income')
                ->where('status', 'APPROVED')
                ->orderBy('transaction_date', 'desc')
                ->first();

            if (!$transaction) {
                continue;
            }

            // 30% chance of return
            if (rand(1, 10) <= 3) {
                $this->createReturnRequest($store, $customer, $transaction, $handler);
            }
        }

        // Create some standalone return requests without linked customers
        for ($i = 0; $i < 3; $i++) {
            $this->createStandaloneReturnRequest($store, $handler);
        }
    }

    private function createReturnRequest(Store $store, Customer $customer, Transaction $transaction, User $handler): void
    {
        $returnDate = $transaction->transaction_date->copy()->addDays(rand(3, 15));
        $resolution = collect(['refund', 'exchange', 'store_credit'])->random();
        $status = collect(['pending', 'in_transit', 'processing', 'completed'])->random();
        
        $returnRequest = ReturnRequest::create([
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'order_number' => $transaction->transaction_id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'product_name' => 'Demo Ürün ' . rand(100, 999),
            'product_sku' => 'SKU-' . rand(1000, 9999),
            'quantity' => rand(1, 3),
            'refund_amount' => $transaction->amount * rand(70, 90) / 100,
            'currency' => $store->currency,
            'return_reason' => collect([
                'Beden uymadı',
                'Renk farklı geldi',
                'Hasarlı ürün',
                'Yanlış ürün gönderildi',
                'Kalite beklentimi karşılamadı',
                'Fotoğraftan farklı'
            ])->random(),
            'status' => $status,
            'resolution' => $status === 'completed' ? $resolution : null,
            'notes' => 'Demo iade talebi',
            'tracking_number' => $status !== 'pending' ? 'KARGO' . rand(100000, 999999) : null,
            'handled_by' => $handler->id,
            'refund_method' => $resolution === 'refund' ? 'cash' : ($resolution === 'store_credit' ? 'store_credit' : 'exchange'),
            'created_at' => $returnDate,
            'updated_at' => $returnDate,
        ]);

        // If completed with store credit, create the credit
        if ($status === 'completed' && $resolution === 'store_credit') {
            $code = 'SC-' . strtoupper(Str::random(8));
            
            StoreCredit::create([
                'company_id' => $store->company_id,
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'return_request_id' => $returnRequest->id,
                'code' => $code,
                'amount' => $returnRequest->refund_amount,
                'remaining_amount' => $returnRequest->refund_amount,
                'currency' => $store->currency,
                'status' => 'active',
                'expires_at' => now()->addYear(),
                'issued_by' => $handler->name,
                'notes' => "İade talebi #{$returnRequest->id} için oluşturuldu",
            ]);
            
            $returnRequest->update(['store_credit_code' => $code]);
        }

        // If completed with exchange, set exchange details
        if ($status === 'completed' && $resolution === 'exchange') {
            $returnRequest->update([
                'exchange_product_name' => 'Değişim Ürün ' . rand(100, 999),
                'exchange_product_sku' => 'SKU-' . rand(1000, 9999),
                'exchange_product_price' => $returnRequest->refund_amount * rand(90, 110) / 100,
                'exchange_difference' => rand(-50, 50),
            ]);
        }
    }

    private function createStandaloneReturnRequest(Store $store, User $handler): void
    {
        $statuses = ['pending', 'in_transit', 'processing', 'completed'];
        $status = collect($statuses)->random();
        
        $returnRequest = ReturnRequest::create([
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'order_number' => 'MANUAL-' . rand(1000, 9999),
            'customer_name' => 'Manuel İade Müşteri ' . rand(1, 100),
            'customer_phone' => '+90555' . rand(1000000, 9999999),
            'customer_email' => 'return' . rand(1, 100) . '@example.com',
            'product_name' => 'İade Ürün ' . rand(100, 999),
            'product_sku' => 'SKU-' . rand(1000, 9999),
            'quantity' => rand(1, 2),
            'refund_amount' => rand(50, 500),
            'currency' => $store->currency,
            'return_reason' => collect([
                'Müşteri memnuniyetsizliği',
                'Ürün açıklamasına uygun değil',
                'Teslimat gecikmesi',
                'Paket hasarlı geldi'
            ])->random(),
            'status' => $status,
            'resolution' => $status === 'completed' ? collect(['refund', 'rejected'])->random() : null,
            'notes' => 'Manuel oluşturulmuş iade talebi',
            'tracking_number' => $status !== 'pending' ? 'MNG' . rand(100000, 999999) : null,
            'handled_by' => $handler->id,
            'refund_method' => 'cash',
            'created_at' => now()->subDays(rand(1, 30)),
        ]);

        // Customer will be auto-created by the model's boot method
    }
}