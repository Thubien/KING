<?php

namespace App\Filament\Resources\PaymentProcessorAccountResource\Pages;

use App\Filament\Resources\PaymentProcessorAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentProcessorAccount extends CreateRecord
{
    protected static string $resource = PaymentProcessorAccountResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        
        return $data;
    }
}