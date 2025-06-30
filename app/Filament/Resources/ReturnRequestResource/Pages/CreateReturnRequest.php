<?php

namespace App\Filament\Resources\ReturnRequestResource\Pages;

use App\Filament\Resources\ReturnRequestResource;
use App\Services\ReturnChecklistService;
use Filament\Resources\Pages\CreateRecord;

class CreateReturnRequest extends CreateRecord
{
    protected static string $resource = ReturnRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        $data['handled_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        // İlk aşama için checklist oluştur
        ReturnChecklistService::createChecklistsForStage($this->record, 'pending');
    }
}
