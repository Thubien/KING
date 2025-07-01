<?php

namespace App\Filament\Pages;

use App\Models\ReturnChecklist;
use App\Models\ReturnRequest;
use App\Models\Transaction;
use App\Services\ReturnChecklistService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ReturnKanban extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'İade Takip';

    protected static string $view = 'filament.pages.return-kanban';

    protected static ?int $navigationSort = 20;

    public $returns;

    public $selectedReturn = null;

    public $showModal = false;

    public function mount()
    {
        $this->loadReturns();
    }

    public function loadReturns()
    {
        $this->returns = ReturnRequest::with(['checklists', 'store', 'handler'])
            ->forCompany() // Güvenlik scope'u kullan
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('status')
            ->mapWithKeys(function ($collection, $key) {
                return [$key => $collection];
            });
    }

    public function moveCard($returnId, $newStatus)
    {
        $return = ReturnRequest::forCompany()->find($returnId);
        
        if (!$return) {
            Notification::make()
                ->title('İade bulunamadı veya erişim yetkiniz yok')
                ->danger()
                ->send();
            return;
        }
        
        $oldStatus = $return->status;

        // Geçerli durum değişimi kontrolü
        $validTransitions = [
            'pending' => ['in_transit', 'completed'],
            'in_transit' => ['processing', 'completed', 'pending'],
            'processing' => ['completed', 'in_transit'],
            'completed' => [], // Tamamlanmış iadeler hareket ettirilemez
        ];

        if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
            Notification::make()
                ->title('Geçersiz durum değişimi')
                ->body("'{$return->status_label}' durumundan '{$newStatus}' durumuna geçiş yapılamaz.")
                ->danger()
                ->send();
            return;
        }

        $return->update(['status' => $newStatus]);

        // Yeni aşama için checklist oluştur
        ReturnChecklistService::createChecklistsForStage($return, $newStatus);

        // İade tamamlandığında finansal işlemleri başlat
        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            try {
                $return->complete();
                
                // Başarı mesajını iade metoduna göre özelleştir
                $successMessage = match($return->refund_method) {
                    'cash' => $return->shouldCreateFinancialRecord() 
                        ? 'İade tamamlandı ve finansal kayıt oluşturuldu' 
                        : 'İade tamamlandı (Shopify - sadece takip)',
                    'exchange' => 'Değişim işlemi tamamlandı',
                    'store_credit' => 'Store credit oluşturuldu: ' . $return->store_credit_code,
                    default => 'İade tamamlandı'
                };
                
                Notification::make()
                    ->title($successMessage)
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                // Hata durumunda eski statüye geri dön
                $return->update(['status' => $oldStatus]);
                
                Notification::make()
                    ->title('Finansal işlem hatası')
                    ->body('İade tamamlanamadı: ' . $e->getMessage())
                    ->danger()
                    ->send();
                
                $this->loadReturns();
                return;
            }
        } else {
            Notification::make()
                ->title('İade durumu güncellendi')
                ->success()
                ->send();
        }

        $this->loadReturns();
    }

    public function toggleChecklist($checklistId)
    {
        $checklist = ReturnChecklist::find($checklistId);
        $checklist->update([
            'is_checked' => ! $checklist->is_checked,
            'checked_at' => $checklist->is_checked ? null : now(),
            'checked_by' => $checklist->is_checked ? null : auth()->id(),
        ]);

        $this->loadReturns();
    }

    public function openReturnModal($returnId)
    {
        $this->dispatch('open-modal', id: 'return-details-'.$returnId);
    }

    public function createReturn($stage = 'pending')
    {
        // Yeni iade oluştur sayfasına yönlendir
        return redirect()->route('filament.admin.resources.return-requests.create');
    }

    public function loadReturnDetails($returnId)
    {
        $this->selectedReturn = ReturnRequest::with(['checklists.checkedBy', 'store', 'handler'])->find($returnId);

        if (! $this->selectedReturn) {
            return;
        }

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedReturn = null;
    }
}
