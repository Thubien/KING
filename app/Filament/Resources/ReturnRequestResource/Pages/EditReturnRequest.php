<?php

namespace App\Filament\Resources\ReturnRequestResource\Pages;

use App\Filament\Resources\ReturnRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use App\Models\ReturnRequest;

class EditReturnRequest extends EditRecord
{
    protected static string $resource = ReturnRequestResource::class;
    
    protected static ?string $title = 'İade Talebi Düzenle';
    
    protected static string $view = 'filament.resources.return-request-resource.pages.edit-return-request';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_kanban')
                ->label('Kanban\'a Dön')
                ->icon('heroicon-o-arrow-left')
                ->url(route('filament.admin.pages.return-kanban'))
                ->color('gray'),
            Actions\DeleteAction::make(),
        ];
    }
    
    public function getTitle(): string
    {
        return "İade #{$this->record->order_number}";
    }
    
    public function getSubheading(): ?string
    {
        return "{$this->record->customer_name} - {$this->record->product_name}";
    }
    
    protected function getViewData(): array
    {
        return [
            'record' => $this->getRecord()
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? route('filament.admin.pages.return-kanban');
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'İade talebi başarıyla güncellendi';
    }
    
    public function toggleChecklist($checklistId)
    {
        $checklist = \App\Models\ReturnChecklist::find($checklistId);
        if ($checklist && $checklist->return_request_id === $this->record->id) {
            $checklist->update([
                'is_checked' => !$checklist->is_checked,
                'checked_at' => $checklist->is_checked ? null : now(),
                'checked_by' => $checklist->is_checked ? null : auth()->id(),
            ]);
            
            $this->refreshFormData(['checklists']);
        }
    }
}
