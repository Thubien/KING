<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ReturnRequest;
use App\Models\Store;
use App\Services\ReturnChecklistService;
use Illuminate\Database\Seeder;

class ReturnRequestDemoSeeder extends Seeder
{
    public function run()
    {
        // İlk company'yi al
        $company = Company::first();
        if (!$company) {
            $this->command->error('Hiç company bulunamadı! Önce php artisan db:seed çalıştırın.');
            return;
        }

        // Shopify ve boutique/physical mağazaları al
        $shopifyStore = Store::where('company_id', $company->id)
            ->where('platform', 'shopify')
            ->first();
            
        $boutiqueStore = Store::where('company_id', $company->id)
            ->whereIn('platform', ['boutique', 'physical'])
            ->first();

        if (!$shopifyStore && !$boutiqueStore) {
            $this->command->error('Uygun mağaza bulunamadı!');
            return;
        }

        // Demo iade talepleri
        $demoReturns = [
            // Shopify - Nakit iade (sadece takip)
            [
                'store_id' => $shopifyStore?->id,
                'order_number' => 'SHP-2024-001',
                'customer_name' => 'Ayşe Yılmaz',
                'customer_phone' => '05551234567',
                'customer_email' => 'ayse@example.com',
                'product_name' => 'Nike Air Max 270',
                'product_sku' => 'NAM270-BLK-42',
                'quantity' => 1,
                'refund_amount' => 150.00,
                'currency' => 'USD',
                'return_reason' => 'Numara küçük geldi, bir büyük numara istiyorum.',
                'status' => 'pending',
                'refund_method' => 'cash',
                'notes' => 'Shopify mağaza - sadece takip amaçlı',
            ],
            
            // Shopify - Store Credit
            [
                'store_id' => $shopifyStore?->id,
                'order_number' => 'SHP-2024-002',
                'customer_name' => 'Mehmet Demir',
                'customer_phone' => '05559876543',
                'customer_email' => 'mehmet@example.com',
                'product_name' => 'Adidas Ultraboost 22',
                'product_sku' => 'AUB22-WHT-43',
                'quantity' => 1,
                'refund_amount' => 200.00,
                'currency' => 'USD',
                'return_reason' => 'Ürün beklentimi karşılamadı.',
                'status' => 'in_transit',
                'refund_method' => 'store_credit',
            ],

            // Butik - Nakit iade (finansal kayıt oluşacak)
            [
                'store_id' => $boutiqueStore?->id,
                'order_number' => 'BTK-2024-001',
                'customer_name' => 'Fatma Kaya',
                'customer_phone' => '05553216549',
                'customer_email' => 'fatma@example.com',
                'product_name' => 'Zara Ceket',
                'product_sku' => 'ZR-JKT-001',
                'quantity' => 1,
                'refund_amount' => 120.00,
                'currency' => 'TRY',
                'return_reason' => 'Renk fotoğraftakinden farklı.',
                'status' => 'processing',
                'refund_method' => 'cash',
                'notes' => 'Butik mağaza - finansal kayıt oluşacak',
            ],

            // Butik - Değişim
            [
                'store_id' => $boutiqueStore?->id,
                'order_number' => 'BTK-2024-002',
                'customer_name' => 'Ali Veli',
                'customer_phone' => '05557894561',
                'customer_email' => 'ali@example.com',
                'product_name' => 'Mango Gömlek',
                'product_sku' => 'MNG-SHR-001',
                'quantity' => 1,
                'refund_amount' => 80.00,
                'currency' => 'TRY',
                'return_reason' => 'Beden değişimi yapmak istiyorum.',
                'status' => 'processing',
                'refund_method' => 'exchange',
                'exchange_product_name' => 'Mango Gömlek - L Beden',
                'exchange_product_sku' => 'MNG-SHR-001-L',
                'exchange_product_price' => 80.00,
                'exchange_difference' => 0,
            ],

            // Shopify - Değişim (fiyat farkı var)
            [
                'store_id' => $shopifyStore?->id,
                'order_number' => 'SHP-2024-003',
                'customer_name' => 'Zeynep Öz',
                'customer_phone' => '05552468135',
                'customer_email' => 'zeynep@example.com',
                'product_name' => 'Puma Sneaker',
                'product_sku' => 'PM-SNK-001',
                'quantity' => 1,
                'refund_amount' => 100.00,
                'currency' => 'USD',
                'return_reason' => 'Başka model istiyorum.',
                'status' => 'pending',
                'refund_method' => 'exchange',
                'exchange_product_name' => 'Puma RS-X',
                'exchange_product_sku' => 'PM-RSX-001',
                'exchange_product_price' => 130.00,
                'exchange_difference' => 30.00, // Müşteri 30 USD ödeyecek
                'notes' => 'Müşteri fark ödeyecek',
            ],

            // Tamamlanmış - Butik Store Credit
            [
                'store_id' => $boutiqueStore?->id,
                'order_number' => 'BTK-2024-003',
                'customer_name' => 'Hasan Çelik',
                'customer_phone' => '05551472583',
                'customer_email' => 'hasan@example.com',
                'product_name' => 'LC Waikiki Pantolon',
                'product_sku' => 'LCW-PNT-001',
                'quantity' => 2,
                'refund_amount' => 160.00,
                'currency' => 'TRY',
                'return_reason' => 'Online alışverişte yanlış beden seçmişim.',
                'status' => 'completed',
                'refund_method' => 'store_credit',
                'resolution' => 'store_credit',
            ],
        ];

        foreach ($demoReturns as $returnData) {
            // Store ID kontrolü
            if (!$returnData['store_id']) {
                continue;
            }

            $return = ReturnRequest::create(array_merge($returnData, [
                'company_id' => $company->id,
            ]));

            // Checklist oluştur
            ReturnChecklistService::createChecklistsForStage($return, $return->status);

            // Tamamlanmış olanları işle
            if ($return->status === 'completed') {
                try {
                    $return->complete();
                } catch (\Exception $e) {
                    // Zaten completed olduğu için hata verecek, ignore et
                }
            }
        }

        $this->command->info('Demo iade talepleri oluşturuldu!');
        $this->command->info('- Shopify mağazalar için: Sadece takip');
        $this->command->info('- Butik mağazalar için: Finansal kayıt');
        $this->command->info('- Farklı iade yöntemleri: Nakit, Değişim, Store Credit');
    }
}