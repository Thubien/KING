<?php

namespace App\Filament\Resources\InventoryItemResource\Pages;

use App\Filament\Resources\InventoryItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryItem extends CreateRecord
{
    protected static string $resource = InventoryItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total_value'] = $data['quantity'] * $data['unit_cost'];
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}