<?php

namespace App\Filament\Resources\BankAccountResource\Pages;

use App\Filament\Resources\BankAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBankAccount extends EditRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure only one primary account
        if ($data['is_primary'] ?? false) {
            \App\Models\BankAccount::where('company_id', $this->record->company_id)
                ->where('id', '!=', $this->record->id)
                ->update(['is_primary' => false]);
        }

        return $data;
    }
}
