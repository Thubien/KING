<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ReturnRequest;
use App\Models\Store;
use App\Services\ReturnChecklistService;
use Illuminate\Database\Seeder;

class ComprehensiveReturnDemoSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Mevcut store yapısı kontrol ediliyor...');
        
        // Store durumunu göster
        $stores = Store::all();
        $this->command->table(
            ['ID', 'Name', 'Platform', 'Company ID'],
            $stores->map(fn($s) => [$s->id, $s->name, $s->platform, $s->company_id])->toArray()
        );

        // İlk company'yi al
        $company = Company::first();
        if (!$company) {
            $this->command->error('Hiç company bulunamadı! Önce php artisan db:seed çalıştırın.');
            return;
        }

        // Mevcut store'ları kullan veya yenilerini oluştur
        $shopifyStore = Store::where('company_id', $company->id)
            ->where('platform', 'shopify')
            ->first();
            
        $boutiqueStore = Store::where('company_id', $company->id)
            ->where('platform', 'boutique')
            ->first();

        $physicalStore = Store::where('company_id', $company->id)
            ->where('platform', 'physical')
            ->first();

        // Eğer yoksa yeni store'lar oluştur
        if (!$shopifyStore) {
            $shopifyStore = Store::create([
                'company_id' => $company->id,
                'name' => 'Online Shopify Store',
                'platform' => 'shopify',
                'currency' => 'USD',
                'status' => 'active',
            ]);
            $this->command->info('Shopify store oluşturuldu: ' . $shopifyStore->name);
        }

        if (!$boutiqueStore) {
            $boutiqueStore = Store::create([
                'company_id' => $company->id,
                'name' => 'Istanbul Boutique',
                'platform' => 'boutique',
                'currency' => 'TRY',
                'status' => 'active',
            ]);
            $this->command->info('Boutique store oluşturuldu: ' . $boutiqueStore->name);
        }

        if (!$physicalStore) {
            $physicalStore = Store::create([
                'company_id' => $company->id,
                'name' => 'Ankara Physical Store',
                'platform' => 'physical',
                'currency' => 'TRY',
                'status' => 'active',
            ]);
            $this->command->info('Physical store oluşturuldu: ' . $physicalStore->name);
        }

        // Önce mevcut demo return'leri temizle
        ReturnRequest::where('notes', 'LIKE', '%DEMO-%')->forceDelete();
        $this->command->info('Eski demo veriler temizlendi.');

        // Kapsamlı demo iade talepleri
        $demoReturns = [
            // SENARYO 1: Shopify - Nakit iade (sadece takip)
            [
                'store_id' => $shopifyStore->id,
                'order_number' => 'SHP-2024-101',
                'customer_name' => 'Ayşe Yılmaz',
                'customer_phone' => '05551234567',
                'customer_email' => 'ayse@example.com',
                'product_name' => 'Nike Air Max 270 - Siyah',
                'product_sku' => 'NAM270-BLK-42',
                'quantity' => 1,
                'refund_amount' => 150.00,
                'currency' => 'USD',
                'return_reason' => 'Numara küçük geldi, bir büyük numara istiyorum.',
                'status' => 'pending',
                'refund_method' => 'cash',
                'notes' => 'DEMO-1: Shopify mağaza nakit iade - sadece takip amaçlı, finansal kayıt YOK',
            ],
            
            // SENARYO 2: Shopify - Store Credit
            [
                'store_id' => $shopifyStore->id,
                'order_number' => 'SHP-2024-102',
                'customer_name' => 'Mehmet Demir',
                'customer_phone' => '05559876543',
                'customer_email' => 'mehmet@example.com',
                'product_name' => 'Adidas Ultraboost 22 - Beyaz',
                'product_sku' => 'AUB22-WHT-43',
                'quantity' => 1,
                'refund_amount' => 200.00,
                'currency' => 'USD',
                'return_reason' => 'Ürün beklentimi karşılamadı, farklı model denemek istiyorum.',
                'status' => 'in_transit',
                'refund_method' => 'store_credit',
                'notes' => 'DEMO-2: Shopify store credit - müşteriye kod verilecek',
            ],

            // SENARYO 3: Butik - Nakit iade (FİNANSAL KAYIT OLUŞACAK!)
            [
                'store_id' => $boutiqueStore->id,
                'order_number' => 'BTK-2024-201',
                'customer_name' => 'Fatma Kaya',
                'customer_phone' => '05553216549',
                'customer_email' => 'fatma@example.com',
                'product_name' => 'Zara Kadın Ceket - Bordo',
                'product_sku' => 'ZR-JKT-001-BRD',
                'quantity' => 1,
                'refund_amount' => 1200.00,
                'currency' => 'TRY',
                'return_reason' => 'Renk fotoğraftakinden çok farklı, beğenmedim.',
                'status' => 'processing',
                'refund_method' => 'cash',
                'notes' => 'DEMO-3: Butik mağaza nakit iade - RETURNS kategorisinde finansal kayıt oluşacak!',
            ],

            // SENARYO 4: Butik - Değişim (aynı fiyat)
            [
                'store_id' => $boutiqueStore->id,
                'order_number' => 'BTK-2024-202',
                'customer_name' => 'Ali Veli',
                'customer_phone' => '05557894561',
                'customer_email' => 'ali@example.com',
                'product_name' => 'Mango Erkek Gömlek - M Beden',
                'product_sku' => 'MNG-SHR-001-M',
                'quantity' => 1,
                'refund_amount' => 800.00,
                'currency' => 'TRY',
                'return_reason' => 'Beden küçük geldi, L beden ile değişim yapmak istiyorum.',
                'status' => 'processing',
                'refund_method' => 'exchange',
                'exchange_product_name' => 'Mango Erkek Gömlek - L Beden',
                'exchange_product_sku' => 'MNG-SHR-001-L',
                'exchange_product_price' => 800.00,
                'exchange_difference' => 0,
                'notes' => 'DEMO-4: Butik değişim - fiyat farkı yok, finansal etki yok',
            ],

            // SENARYO 5: Physical Store - Değişim (müşteri fark ödeyecek)
            [
                'store_id' => $physicalStore->id,
                'order_number' => 'PHY-2024-301',
                'customer_name' => 'Zeynep Öz',
                'customer_phone' => '05552468135',
                'customer_email' => 'zeynep@example.com',
                'product_name' => 'Puma Basic Sneaker',
                'product_sku' => 'PM-SNK-001',
                'quantity' => 1,
                'refund_amount' => 1000.00,
                'currency' => 'TRY',
                'return_reason' => 'Daha kaliteli model istiyorum.',
                'status' => 'pending',
                'refund_method' => 'exchange',
                'exchange_product_name' => 'Puma RS-X Premium',
                'exchange_product_sku' => 'PM-RSX-001',
                'exchange_product_price' => 1300.00,
                'exchange_difference' => 300.00, // Müşteri 300 TRY ödeyecek
                'notes' => 'DEMO-5: Physical store değişim - müşteri 300 TRY fark ödeyecek',
            ],

            // SENARYO 6: Physical Store - Store Credit (tamamlanmış)
            [
                'store_id' => $physicalStore->id,
                'order_number' => 'PHY-2024-302',
                'customer_name' => 'Hasan Çelik',
                'customer_phone' => '05551472583',
                'customer_email' => 'hasan@example.com',
                'product_name' => 'LC Waikiki Erkek Pantolon (2 adet)',
                'product_sku' => 'LCW-PNT-001',
                'quantity' => 2,
                'refund_amount' => 600.00,
                'currency' => 'TRY',
                'return_reason' => 'Online alışverişte yanlış beden seçmişim, ikisi de büyük.',
                'status' => 'completed',
                'refund_method' => 'store_credit',
                'resolution' => 'store_credit',
                'notes' => 'DEMO-6: Physical store credit - 600 TRY değerinde kod verildi',
            ],

            // SENARYO 7: Shopify - Değişim (müşteriye iade)
            [
                'store_id' => $shopifyStore->id,
                'order_number' => 'SHP-2024-103',
                'customer_name' => 'Elif Yıldız',
                'customer_phone' => '05558527419',
                'customer_email' => 'elif@example.com',
                'product_name' => 'Apple Watch Series 8',
                'product_sku' => 'APL-WS8-45MM',
                'quantity' => 1,
                'refund_amount' => 500.00,
                'currency' => 'USD',
                'return_reason' => 'SE modeli almak istiyorum, daha uygun fiyatlı.',
                'status' => 'in_transit',
                'refund_method' => 'exchange',
                'exchange_product_name' => 'Apple Watch SE',
                'exchange_product_sku' => 'APL-WSE-44MM',
                'exchange_product_price' => 300.00,
                'exchange_difference' => -200.00, // Biz müşteriye 200 USD iade edeceğiz
                'notes' => 'DEMO-7: Shopify değişim - müşteriye 200 USD iade',
            ],

            // SENARYO 8: Butik - Nakit iade (beklemede)
            [
                'store_id' => $boutiqueStore->id,
                'order_number' => 'BTK-2024-203',
                'customer_name' => 'Ahmet Kara',
                'customer_phone' => '05557539514',
                'customer_email' => 'ahmet@example.com',
                'product_name' => 'H&M Kazak',
                'product_sku' => 'HM-KZK-001',
                'quantity' => 1,
                'refund_amount' => 450.00,
                'currency' => 'TRY',
                'return_reason' => 'Hediye olarak aldım ama beğenilmedi.',
                'status' => 'pending',
                'refund_method' => 'cash',
                'notes' => 'DEMO-8: Butik nakit iade beklemede',
            ],

            // SENARYO 9: Physical Store - Çoklu ürün iadesi
            [
                'store_id' => $physicalStore->id,
                'order_number' => 'PHY-2024-303',
                'customer_name' => 'Selin Demirci',
                'customer_phone' => '05553698521',
                'customer_email' => 'selin@example.com',
                'product_name' => 'Koton Tişört Seti (3 adet)',
                'product_sku' => 'KTN-TSH-SET',
                'quantity' => 3,
                'refund_amount' => 300.00,
                'currency' => 'TRY',
                'return_reason' => 'Kumaş kalitesi kötü, ilk yıkamada soldu.',
                'status' => 'processing',
                'refund_method' => 'cash',
                'notes' => 'DEMO-9: Physical store çoklu ürün nakit iadesi',
            ],

            // SENARYO 10: Shopify - İptal edilmiş iade
            [
                'store_id' => $shopifyStore->id,
                'order_number' => 'SHP-2024-104',
                'customer_name' => 'Can Özkan',
                'customer_phone' => '05551597534',
                'customer_email' => 'can@example.com',
                'product_name' => 'Sony WH-1000XM4',
                'product_sku' => 'SNY-WH4-BLK',
                'quantity' => 1,
                'refund_amount' => 350.00,
                'currency' => 'USD',
                'return_reason' => 'Ses kalitesi beklediğim gibi değil.',
                'status' => 'completed',
                'refund_method' => 'cash',
                'resolution' => 'rejected',
                'notes' => 'DEMO-10: Shopify iade talebi reddedildi - garanti dışı',
            ],
        ];

        $createdCount = 0;
        foreach ($demoReturns as $returnData) {
            try {
                $return = ReturnRequest::create(array_merge($returnData, [
                    'company_id' => $company->id,
                ]));

                // Checklist oluştur
                ReturnChecklistService::createChecklistsForStage($return, $return->status);

                // Tamamlanmış olanları işle (complete metodunu çağırma, zaten completed)
                if ($return->status === 'completed' && $return->resolution !== 'rejected') {
                    // Store credit için kod oluştur
                    if ($return->refund_method === 'store_credit') {
                        $return->createStoreCredit();
                    }
                    // Finansal kayıt oluştur (sadece uygun olanlar için)
                    if ($return->shouldCreateFinancialRecord()) {
                        $return->createFinancialTransaction();
                    }
                }

                $createdCount++;
                $this->command->info("✓ {$returnData['notes']}");
            } catch (\Exception $e) {
                $this->command->error("✗ Hata: " . $e->getMessage());
            }
        }

        $this->command->info("\n🎉 Toplam {$createdCount} demo iade talebi oluşturuldu!");
        
        // Özet tablo göster
        $summary = ReturnRequest::selectRaw('
            stores.name as store_name,
            stores.platform,
            return_requests.refund_method,
            COUNT(*) as count,
            SUM(return_requests.refund_amount) as total_amount,
            return_requests.currency
        ')
        ->join('stores', 'stores.id', '=', 'return_requests.store_id')
        ->groupBy('stores.name', 'stores.platform', 'return_requests.refund_method', 'return_requests.currency')
        ->get();

        $this->command->info("\n📊 Özet:");
        $this->command->table(
            ['Mağaza', 'Platform', 'İade Yöntemi', 'Adet', 'Toplam Tutar'],
            $summary->map(fn($s) => [
                $s->store_name,
                $s->platform,
                $s->refund_method,
                $s->count,
                number_format($s->total_amount, 2) . ' ' . $s->currency
            ])->toArray()
        );
    }
}