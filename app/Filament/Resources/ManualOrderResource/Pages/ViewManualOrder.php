<?php

namespace App\Filament\Resources\ManualOrderResource\Pages;

use App\Filament\Resources\ManualOrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewManualOrder extends ViewRecord
{
    protected static string $resource = ManualOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }
}