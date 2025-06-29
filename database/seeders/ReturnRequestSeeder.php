<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReturnRequest;
use App\Models\Store;
use App\Models\User;
use App\Services\ReturnChecklistService;

class ReturnRequestSeeder extends Seeder
{
    public function run()
    {
        $stores = Store::all();
        $users = User::all();
        
        if ($stores->isEmpty() || $users->isEmpty()) {
            return;
        }
        
        $returns = [
            [
                'order_number' => 'SHP-2024-001',
                'customer_name' => 'Ayşe Yılmaz',
                'customer_phone' => '05551234567',
                'product_name' => 'Kadın Çanta - Siyah',
                'return_reason' => 'Ürün beklediğimden küçük geldi',
                'status' => 'pending',
                'notes' => 'Müşteri değişim istiyor',
            ],
            [
                'order_number' => 'SHP-2024-002',
                'customer_name' => 'Mehmet Demir',
                'customer_phone' => '05552345678',
                'product_name' => 'Erkek Ayakkabı - 42 Numara',
                'return_reason' => 'Ayakkabı numarası büyük geldi',
                'status' => 'in_transit',
                'tracking_number' => 'YK123456789',
                'notes' => 'Kargo yolda',
            ],
            [
                'order_number' => 'SHP-2024-003',
                'customer_name' => 'Fatma Kaya',
                'customer_phone' => '05553456789',
                'product_name' => 'Kadın Elbise - Mavi',
                'return_reason' => 'Renk fotoğraftan farklı',
                'status' => 'processing',
                'tracking_number' => 'YK987654321',
                'notes' => 'Ürün kontrol ediliyor',
            ],
            [
                'order_number' => 'SHP-2024-004',
                'customer_name' => 'Ali Öztürk',
                'customer_phone' => '05554567890',
                'product_name' => 'Erkek Gömlek - L Beden',
                'return_reason' => 'Ürünü beğenmedim',
                'status' => 'completed',
                'resolution' => 'refund',
                'tracking_number' => 'YK111222333',
                'notes' => 'Para iadesi yapıldı',
            ],
        ];
        
        foreach ($returns as $returnData) {
            $return = ReturnRequest::create([
                'company_id' => $stores->first()->company_id,
                'store_id' => $stores->random()->id,
                'order_number' => $returnData['order_number'],
                'customer_name' => $returnData['customer_name'],
                'customer_phone' => $returnData['customer_phone'],
                'product_name' => $returnData['product_name'],
                'return_reason' => $returnData['return_reason'],
                'status' => $returnData['status'],
                'resolution' => $returnData['resolution'] ?? null,
                'tracking_number' => $returnData['tracking_number'] ?? null,
                'notes' => $returnData['notes'] ?? null,
                'handled_by' => $users->random()->id,
            ]);
            
            // Her aşama için checklist oluştur
            ReturnChecklistService::createChecklistsForStage($return, $return->status);
            
            // Bazı checklist'leri işaretle
            if ($return->status !== 'pending') {
                $checklists = $return->checklists()->where('stage', 'pending')->get();
                foreach ($checklists as $checklist) {
                    $checklist->update([
                        'is_checked' => true,
                        'checked_at' => now()->subDays(rand(1, 5)),
                        'checked_by' => $users->random()->id,
                    ]);
                }
            }
            
            if (in_array($return->status, ['processing', 'completed'])) {
                $checklists = $return->checklists()->where('stage', 'in_transit')->take(2)->get();
                foreach ($checklists as $checklist) {
                    $checklist->update([
                        'is_checked' => true,
                        'checked_at' => now()->subDays(rand(1, 3)),
                        'checked_by' => $users->random()->id,
                    ]);
                }
            }
        }
    }
}