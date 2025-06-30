<?php

namespace App\Filament\Resources\BankAccountResource\Pages;

use App\Filament\Resources\BankAccountResource;
use App\Models\BankAccount;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBankAccounts extends ListRecords
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('createPrimary')
                ->label('Create Primary Account')
                ->icon('heroicon-m-star')
                ->color('warning')
                ->action(function (): void {
                    $company = auth()->user()->company;
                    BankAccount::createDefault($company->id, $company->primary_currency ?? 'USD');
                })
                ->visible(function (): bool {
                    $company = auth()->user()->company;

                    return BankAccount::where('company_id', $company->id)->count() === 0;
                }),
        ];
    }
}
