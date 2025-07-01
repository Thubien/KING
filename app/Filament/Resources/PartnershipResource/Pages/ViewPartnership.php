<?php

namespace App\Filament\Resources\PartnershipResource\Pages;

use App\Filament\Resources\PartnershipResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPartnership extends ViewRecord
{
    protected static string $resource = PartnershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }
}