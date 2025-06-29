<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\ReturnRequest;
use App\Models\ReturnChecklist;
use App\Services\ReturnChecklistService;
use Filament\Notifications\Notification;

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
        $this->returns = ReturnRequest::with(['checklists', 'store'])
            ->where('company_id', auth()->user()->company_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('status')
            ->mapWithKeys(function ($collection, $key) {
                return [$key => $collection];
            });
    }
    
    public function moveCard($returnId, $newStatus)
    {
        $return = ReturnRequest::find($returnId);
        $oldStatus = $return->status;
        
        $return->update(['status' => $newStatus]);
        
        // Yeni aşama için checklist oluştur
        ReturnChecklistService::createChecklistsForStage($return, $newStatus);
        
        Notification::make()
            ->title('İade durumu güncellendi')
            ->success()
            ->send();
        
        $this->loadReturns();
    }
    
    public function toggleChecklist($checklistId)
    {
        $checklist = ReturnChecklist::find($checklistId);
        $checklist->update([
            'is_checked' => !$checklist->is_checked,
            'checked_at' => $checklist->is_checked ? null : now(),
            'checked_by' => $checklist->is_checked ? null : auth()->id(),
        ]);
        
        $this->loadReturns();
    }
    
    public function openReturnModal($returnId)
    {
        $this->dispatch('open-modal', id: 'return-details-' . $returnId);
    }
    
    public function createReturn($stage = 'pending')
    {
        // Yeni iade oluştur sayfasına yönlendir
        return redirect()->route('filament.admin.resources.return-requests.create');
    }
    
    public function loadReturnDetails($returnId)
    {
        $this->selectedReturn = ReturnRequest::with(['checklists.checkedBy', 'store', 'handler'])->find($returnId);
        
        if (!$this->selectedReturn) {
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