<?php

namespace App\Filament\Resources\StoreCreditResource\Pages;

use App\Filament\Resources\StoreCreditResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStoreCredit extends EditRecord
{
    protected static string $resource = StoreCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
