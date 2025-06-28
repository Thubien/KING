<?php

namespace App\Filament\Resources\ManualOrderResource\Pages;

use App\Filament\Resources\ManualOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManualOrder extends EditRecord
{
    protected static string $resource = ManualOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
