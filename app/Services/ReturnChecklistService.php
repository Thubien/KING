<?php

namespace App\Services;

use App\Models\ReturnChecklist;

class ReturnChecklistService
{
    public static function getStageChecklists()
    {
        return [
            'pending' => [
                'İade talebi alındı',
                'Müşteri bilgileri doğrulandı',
                'İade onayı verildi',
                'Kargo bilgisi iletildi',
            ],
            
            'in_transit' => [
                'Kargo takip numarası alındı',
                'Ürün kargoya verildi',
                'Kargo takibi yapılıyor',
            ],
            
            'processing' => [
                'Ürün teslim alındı',
                'Ürün kontrol edildi',
                'Karar verildi (kabul/red)',
                'İşlem başlatıldı',
                'Müşteri bilgilendirildi',
            ],
            
            'completed' => [
                'İşlem tamamlandı',
                'Kayıtlar güncellendi',
            ],
        ];
    }

    public static function createChecklistsForStage($returnRequest, $stage)
    {
        $items = self::getStageChecklists()[$stage] ?? [];
        
        foreach ($items as $item) {
            ReturnChecklist::create([
                'return_request_id' => $returnRequest->id,
                'stage' => $stage,
                'item_text' => $item,
                'is_checked' => false,
            ]);
        }
    }
}