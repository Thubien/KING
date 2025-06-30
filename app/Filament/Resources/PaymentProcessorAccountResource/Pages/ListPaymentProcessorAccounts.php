<?php

namespace App\Filament\Resources\PaymentProcessorAccountResource\Pages;

use App\Filament\Resources\PaymentProcessorAccountResource;
use App\Models\PaymentProcessorAccount;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentProcessorAccounts extends ListRecords
{
    protected static string $resource = PaymentProcessorAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('createDefaults')
                ->label('Create Default Processors')
                ->icon('heroicon-m-plus-circle')
                ->color('info')
                ->action(function (): void {
                    $company = auth()->user()->company;
                    PaymentProcessorAccount::createDefault($company->id, $company->primary_currency ?? 'USD');
                })
                ->visible(function (): bool {
                    $company = auth()->user()->company;

                    return PaymentProcessorAccount::where('company_id', $company->id)->count() === 0;
                }),
        ];
    }
}
