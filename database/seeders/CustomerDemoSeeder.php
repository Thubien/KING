<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\ReturnRequest;
use App\Models\StoreCredit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CustomerDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'EcomBoard Demo Company')->first();
        if (!$company) {
            $this->command->error('Demo company not found. Please run DemoDataSeeder first.');
            return;
        }

        $stores = Store::where('company_id', $company->id)->get();
        if ($stores->isEmpty()) {
            $this->command->error('No stores found for demo company.');
            return;
        }

        $salesRep = User::where('email', 'salesrep@demo.com')->first();

        foreach ($stores as $store) {
            $this->createCustomersForStore($store, $salesRep);
        }

        $this->command->info('Customer demo data created successfully!');
        $this->command->info('Created VIP, Loyal, At Risk, Lost, and New customers with full histories.');
    }

    private function createCustomersForStore(Store $store, ?User $salesRep): void
    {
        // VIP Customer
        $vipCustomer = $this->createVipCustomer($store);
        
        // Loyal Customers
        for ($i = 0; $i < 3; $i++) {
            $this->createLoyalCustomer($store, $i);
        }
        
        // At Risk Customers
        for ($i = 0; $i < 2; $i++) {
            $this->createAtRiskCustomer($store, $i);
        }
        
        // Lost Customers
        for ($i = 0; $i < 2; $i++) {
            $this->createLostCustomer($store, $i);
        }
        
        // New Customers
        for ($i = 0; $i < 5; $i++) {
            $this->createNewCustomer($store, $i);
        }
        
        // Problematic Customer
        $this->createProblematicCustomer($store);
        
        // B2B Customer
        $this->createB2BCustomer($store);
    }

    private function createVipCustomer(Store $store): Customer
    {
        $customer = Customer::create([
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'name' => 'Ahmet Yılmaz',
            'email' => 'ahmet.yilmaz@vipmail.com',
            'phone' => '+905551234567',
            'whatsapp_number' => '905551234567',
            'gender' => 'male',
            'birth_date' => now()->subYears(35),
            'tags' => ['vip', 'loyal'],
            'notes' => 'Çok değerli müşterimiz. Özel günlerde indirim kuponu gönder.',
            'source' => 'manual',
            'status' => 'active',
            'accepts_marketing' => true,
            'preferred_contact_method' => 'whatsapp',
        ]);

        // Create addresses
        CustomerAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Ev',
            'type' => 'both',
            'full_name' => $customer->name,
            'address_line_1' => 'Nişantaşı Mah. Abdi İpekçi Cad. No:45/3',
            'city' => 'İstanbul',
            'district' => 'Şişli',
            'postal_code' => '34367',
            'country' => 'TR',
            'phone' => $customer->phone,
            'is_default' => true,
        ]);

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'label' => 'İş',
            'type' => 'shipping',
            'full_name' => $customer->name,
            'address_line_1' => 'Maslak Mah. Büyükdere Cad. Spine Tower K:25',
            'city' => 'İstanbul',
            'district' => 'Sarıyer',
            'postal_code' => '34398',
            'country' => 'TR',
            'phone' => '+902123334455',
            'is_default' => false,
        ]);

        // Create purchase history - 15 orders over 8 months
        $totalSpent = 0;
        for ($month = 7; $month >= 0; $month--) {
            for ($order = 0; $order < rand(1, 3); $order++) {
                $orderDate = now()->subMonths($month)->subDays(rand(0, 25));
                $amount = rand(500, 2500);
                $totalSpent += $amount;
                
                $transaction = Transaction::create([
                    'store_id' => $store->id,
                    'customer_id' => $customer->id,
                    'transaction_id' => 'VIP-' . $orderDate->format('Ymd') . '-' . strtoupper(Str::random(4)),
                    'amount' => $amount,
                    'currency' => $store->currency,
                    'amount_usd' => $amount,
                    'category' => 'SALES',
                    'type' => 'income',
                    'status' => 'APPROVED',
                    'description' => 'VIP Müşteri Siparişi #' . rand(10000, 99999),
                    'transaction_date' => $orderDate,
                    'sales_channel' => collect(['shopify', 'instagram', 'whatsapp'])->random(),
                    'payment_method' => collect(['credit_card', 'bank_transfer'])->random(),
                    'data_source' => 'manual_entry',
                    'customer_info' => [
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                    ],
                    'created_by' => 1, // Admin user
                ]);

                // Log timeline event
                $customer->logTimelineEvent(
                    'order_placed',
                    'Sipariş alındı',
                    "Tutar: {$amount} {$store->currency}",
                    [
                        'amount' => $amount,
                        'currency' => $store->currency,
                        'payment_method' => $transaction->payment_method,
                        'sales_channel' => $transaction->sales_channel,
                    ],
                    'Transaction',
                    $transaction->id
                );
            }
        }

        // Update customer statistics
        $customer->updateStatistics();
        
        // Add VIP tag if not already added
        if (!$customer->hasTag('vip')) {
            $customer->addTag('vip');
        }

        return $customer;
    }

    private function createLoyalCustomer(Store $store, int $index): Customer
    {
        $names = ['Zeynep Kaya', 'Mehmet Demir', 'Ayşe Çelik'];
        $emails = ['zeynep.k@gmail.com', 'mehmet.demir@hotmail.com', 'ayse.celik@yahoo.com'];
        
        $customer = Customer::create([
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'name' => $names[$index] ?? 'Sadık Müşteri ' . ($index + 1),
            'email' => $emails[$index] ?? 'loyal' . ($index + 1) . '@example.com',
            'phone' => '+90555' . rand(1000000, 9999999),
            'whatsapp_number' => '90555' . rand(1000000, 9999999),
            'gender' => $index % 2 == 0 ? 'female' : 'male',
            'tags' => ['loyal'],
            'source' => 'manual',
            'status' => 'active',
            'accepts_marketing' => true,
            'preferred_contact_method' => 'email',
        ]);

        // Address
        CustomerAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Ev',
            'type' => 'both',
            'full_name' => $customer->name,
            'address_line_1' => 'Örnek Mah. Test Sok. No:' . rand(1, 100),
            'city' => collect(['İstanbul', 'Ankara', 'İzmir'])->random(),
            'district' => 'Merkez',
            'postal_code' => rand(10000, 99999),
            'country' => 'TR',
            'phone' => $customer->phone,
            'is_default' => true,
        ]);

        // Create 6-8 orders over 6 months
        $orderCount = rand(6, 8);
        for ($i = 0; $i < $orderCount; $i++) {
            $orderDate = now()->subMonths(rand(0, 5))->subDays(rand(0, 28));
            $amount = rand(150, 800);
            
            $transaction = Transaction::create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'transaction_id' => 'LOYAL-' . $orderDate->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'amount' => $amount,
                'currency' => $store->currency,
                'amount_usd' => $amount,
                'category' => 'SALES',
                'type' => 'income',
                'status' => 'APPROVED',
                'description' => 'Sadık Müşteri Siparişi',
                'transaction_date' => $orderDate,
                'sales_channel' => 'shopify',
                'payment_method' => 'credit_card',
                'data_source' => 'manual_entry',
                'customer_info' => [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                ],
                'created_by' => 1,
            ]);
        }

        $customer->updateStatistics();
        return $customer;
    }

    private function createAtRiskCustomer(Store $store, int $index): Customer
    {
        $customer = Customer::create([
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'name' => 'Risk Müşteri ' . ($index + 1),
            'email' => 'atrisk' . ($index + 1) . '@example.com',
            'phone' => '+90544' . rand(1000000, 9999999),
            'tags' => [],
            'source' => 'shopify',
            'status' => 'active',
            'accepts_marketing' => false,
            'notes' => 'Son 4 aydır alışveriş yapmadı. İletişime geç.',
        ]);

        // Create 3-4 old orders
        for ($i = 0; $i < rand(3, 4); $i++) {
            $orderDate = now()->subMonths(rand(4, 8))->subDays(rand(0, 28));
            $amount = rand(100, 500);
            
            Transaction::create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'transaction_id' => 'RISK-' . $orderDate->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'amount' => $amount,
                'currency' => $store->currency,
                'amount_usd' => $amount,
                'category' => 'SALES',
                'type' => 'income',
                'status' => 'APPROVED',
                'description' => 'Geçmiş Sipariş',
                'transaction_date' => $orderDate,
                'sales_channel' => 'shopify',
                'payment_method' => 'credit_card',
                'data_source' => 'shopify_api',
                'created_by' => 1,
            ]);
        }

        $customer->updateStatistics();
        return $customer;
    }

    private function createLostCustomer(Store $store, int $index): Customer
    {
        $customer = Customer::create([
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'name' => 'Kayıp Müşteri ' . ($index + 1),
            'email' => 'lost' . ($index + 1) . '@oldcustomer.com',
            'phone' => '+90533' . rand(1000000, 9999999),
            'tags' => [],
            'source' => 'manual',
            'status' => 'inactive',
            'accepts_marketing' => false,
        ]);

        // Create 1-2 very old orders
        for ($i = 0; $i < rand(1, 2); $i++) {
            $orderDate = now()->subMonths(rand(7, 12))->subDays(rand(0, 28));
            $amount = rand(50, 300);
            
            Transaction::create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'transaction_id' => 'LOST-' . $orderDate->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'amount' => $amount,
                'currency' => $store->currency,
                'amount_usd' => $amount,
                'category' => 'SALES',
                'type' => 'income',
                'status' => 'APPROVED',
                'description' => 'Eski Sipariş',
                'transaction_date' => $orderDate,
                'sales_channel' => 'instagram',
                'payment_method' => 'bank_transfer',
                'data_source' => 'manual_entry',
                'created_by' => 1,
            ]);
        }

        $customer->updateStatistics();
        return $customer;
    }

    private function createNewCustomer(Store $store, int $index): Customer
    {
        $customer = Customer::create([
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'name' => 'Yeni Müşteri ' . ($index + 1),
            'email' => 'new' . ($index + 1) . '@newcustomer.com',
            'phone' => '+90532' . rand(1000000, 9999999),
            'whatsapp_number' => '90532' . rand(1000000, 9999999),
            'tags' => ['new'],
            'source' => collect(['shopify', 'manual', 'return'])->random(),
            'status' => 'active',
            'accepts_marketing' => true,
            'preferred_contact_method' => 'whatsapp',
        ]);

        // Create 1 recent order
        $orderDate = now()->subDays(rand(1, 30));
        $amount = rand(75, 400);
        
        $transaction = Transaction::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'transaction_id' => 'NEW-' . $orderDate->format('Ymd') . '-' . strtoupper(Str::random(4)),
            'amount' => $amount,
            'currency' => $store->currency,
            'amount_usd' => $amount,
            'category' => 'SALES',
            'type' => 'income',
            'status' => 'APPROVED',
            'description' => 'İlk Sipariş - Hoş geldin indirimi uygulandı',
            'transaction_date' => $orderDate,
            'sales_channel' => collect(['shopify', 'instagram', 'whatsapp'])->random(),
            'payment_method' => collect(['credit_card', 'cash_on_delivery'])->random(),
            'data_source' => 'manual_entry',
            'customer_info' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'notes' => 'İlk alışveriş',
            ],
            'created_by' => 1,
        ]);

        // Add welcome timeline event
        $customer->logTimelineEvent(
            'note_added',
            'Hoş geldiniz mesajı gönderildi',
            'Yeni müşteriye hoş geldiniz e-postası ve %10 indirim kuponu gönderildi.',
            ['discount_code' => 'WELCOME10']
        );

        $customer->updateStatistics();
        return $customer;
    }

    private function createProblematicCustomer(Store $store): Customer
    {
        $customer = Customer::create([
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'name' => 'Sorunlu Müşteri',
            'email' => 'problematic@customer.com',
            'phone' => '+90542' . rand(1000000, 9999999),
            'tags' => ['problematic', 'returning'],
            'source' => 'manual',
            'status' => 'active',
            'accepts_marketing' => false,
            'notes' => 'Sürekli iade yapıyor. Siparişleri dikkatli kontrol et.',
        ]);

        // Create orders with high return rate
        for ($i = 0; $i < 5; $i++) {
            $orderDate = now()->subMonths(rand(0, 3))->subDays(rand(0, 28));
            $amount = rand(200, 600);
            
            $transaction = Transaction::create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'transaction_id' => 'PROB-' . $orderDate->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'amount' => $amount,
                'currency' => $store->currency,
                'amount_usd' => $amount,
                'category' => 'SALES',
                'type' => 'income',
                'status' => 'APPROVED',
                'description' => 'Sipariş - Dikkat: Sorunlu müşteri',
                'transaction_date' => $orderDate,
                'sales_channel' => 'shopify',
                'payment_method' => 'cash_on_delivery',
                'data_source' => 'manual_entry',
                'created_by' => 1,
            ]);

            // 60% chance of return
            if (rand(1, 10) <= 6) {
                $returnDate = $orderDate->copy()->addDays(rand(3, 10));
                
                $returnRequest = ReturnRequest::create([
                    'company_id' => $store->company_id,
                    'store_id' => $store->id,
                    'customer_id' => $customer->id,
                    'order_number' => $transaction->transaction_id,
                    'customer_name' => $customer->name,
                    'customer_phone' => $customer->phone,
                    'customer_email' => $customer->email,
                    'product_name' => 'Ürün ' . rand(100, 999),
                    'product_sku' => 'SKU-' . rand(1000, 9999),
                    'quantity' => 1,
                    'refund_amount' => $amount * 0.9, // %10 kargo kesintisi
                    'currency' => $store->currency,
                    'return_reason' => collect(['Beden uymadı', 'Renk farklı', 'Kalite beklentimi karşılamadı', 'Yanlış ürün'])->random(),
                    'status' => 'completed',
                    'resolution' => collect(['refund', 'exchange'])->random(),
                    'refund_method' => 'cash',
                    'handled_by' => 1,
                    'created_at' => $returnDate,
                    'updated_at' => $returnDate,
                ]);

                // Create return transaction
                Transaction::create([
                    'store_id' => $store->id,
                    'customer_id' => $customer->id,
                    'transaction_id' => 'RETURN-' . $returnDate->format('Ymd') . '-' . strtoupper(Str::random(4)),
                    'amount' => -($amount * 0.9),
                    'currency' => $store->currency,
                    'amount_usd' => -($amount * 0.9),
                    'category' => 'RETURNS',
                    'type' => 'expense',
                    'status' => 'APPROVED',
                    'description' => 'İade - ' . $returnRequest->return_reason,
                    'transaction_date' => $returnDate,
                    'reference_number' => 'RETURN-' . $returnRequest->id,
                    'created_by' => 1,
                ]);
            }
        }

        $customer->updateStatistics();
        return $customer;
    }

    private function createB2BCustomer(Store $store): Customer
    {
        $customer = Customer::create([
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'name' => 'ABC Tekstil Ltd. Şti.',
            'email' => 'muhasebe@abctekstil.com',
            'phone' => '+902123456789',
            'company_name' => 'ABC Tekstil Ltd. Şti.',
            'tax_number' => '1234567890',
            'tags' => ['wholesale', 'b2b'],
            'source' => 'manual',
            'status' => 'active',
            'accepts_marketing' => true,
            'preferred_contact_method' => 'email',
            'notes' => 'Toptan müşteri. Özel fiyat listesi uygulanıyor. Vade: 30 gün',
        ]);

        // B2B Address
        CustomerAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Merkez Ofis',
            'type' => 'both',
            'full_name' => $customer->company_name,
            'address_line_1' => 'Organize Sanayi Bölgesi 3. Cadde No:45',
            'city' => 'Bursa',
            'district' => 'Osmangazi',
            'postal_code' => '16140',
            'country' => 'TR',
            'phone' => $customer->phone,
            'is_default' => true,
        ]);

        // Create bulk orders
        for ($i = 0; $i < 8; $i++) {
            $orderDate = now()->subMonths(rand(0, 6))->subDays(rand(0, 28));
            $amount = rand(2000, 10000); // Larger B2B orders
            
            $transaction = Transaction::create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'transaction_id' => 'B2B-' . $orderDate->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'amount' => $amount,
                'currency' => $store->currency,
                'amount_usd' => $amount,
                'category' => 'SALES',
                'type' => 'income',
                'status' => 'APPROVED',
                'description' => 'Toptan Sipariş - Fatura No: ' . rand(100000, 999999),
                'transaction_date' => $orderDate,
                'sales_channel' => 'whatsapp',
                'payment_method' => 'bank_transfer',
                'data_source' => 'manual_entry',
                'customer_info' => [
                    'name' => $customer->company_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'tax_number' => $customer->tax_number,
                ],
                'created_by' => 1,
            ]);

            // Add timeline event for large orders
            if ($amount > 5000) {
                $customer->logTimelineEvent(
                    'note_added',
                    'Büyük sipariş alındı',
                    "Tutar: {$amount} {$store->currency} - Özel kargo ile gönderilecek",
                    ['amount' => $amount, 'special_shipping' => true],
                    'Transaction',
                    $transaction->id
                );
            }
        }

        // Create a store credit for this B2B customer
        StoreCredit::create([
            'company_id' => $store->company_id,
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'code' => 'B2B-' . strtoupper(Str::random(8)),
            'amount' => 500,
            'remaining_amount' => 500,
            'currency' => $store->currency,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'status' => 'active',
            'expires_at' => now()->addYear(),
            'issued_by' => 'System',
            'notes' => 'Toptan alım bonusu',
        ]);

        $customer->updateStatistics();
        return $customer;
    }
}