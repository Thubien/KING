<?php

namespace App\Filament\Resources\BankAccountResource\Pages;

use App\Filament\Resources\BankAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        
        // Ensure only one primary account
        if ($data['is_primary'] ?? false) {
            \App\Models\BankAccount::where('company_id', $data['company_id'])
                ->update(['is_primary' => false]);
        }
        
        return $data;
    }
}