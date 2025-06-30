<?php

namespace App\Filament\Resources\PaymentProcessorAccountResource\Pages;

use App\Filament\Resources\PaymentProcessorAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentProcessorAccount extends EditRecord
{
    protected static string $resource = PaymentProcessorAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
