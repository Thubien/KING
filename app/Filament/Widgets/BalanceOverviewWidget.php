<?php

namespace App\Filament\Widgets;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\PaymentProcessorAccount;
use App\Services\BalanceValidationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class BalanceOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    // Auto refresh every 30 seconds
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $company = Company::find(auth()->user()->company_id);

        if (! $company) {
            return [];
        }

        $balanceService = new BalanceValidationService;
        $balanceResult = $balanceService->getCachedBalance($company);

        $bankTotal = $this->getBankAccountsTotal($company);
        $processorTotal = $this->getProcessorAccountsTotal($company);
        $pendingTotal = $this->getPendingBalanceTotal($company);
        $inventoryTotal = $balanceResult['inventory_total'] ?? 0;

        return [
            Stat::make('Cash Total', $this->formatMoney($balanceResult['cash_total'] ?? $balanceResult['real_money_total']))
                ->description('Bank + Processors')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Inventory Value', $this->formatMoney($inventoryTotal))
                ->description('Stock value')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Total Assets', $this->formatMoney($balanceResult['total_assets'] ?? $balanceResult['real_money_total']))
                ->description('Cash + Inventory')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info')
                ->chart([7, 5, 10, 3, 15, 12, 17])
                ->chartColor('info'),

            Stat::make('Pending Payouts', $this->formatMoney($pendingTotal))
                ->description('Waiting for payout')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Calculated Balance', $this->formatMoney($balanceResult['calculated_balance']))
                ->description('From store transactions')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('gray'),

            Stat::make('Balance Status', $balanceResult['is_valid'] ? 'VALID' : 'ERROR')
                ->description($balanceResult['is_valid']
                    ? 'All balances match'
                    : 'Difference: '.$this->formatMoney($balanceResult['difference'])
                )
                ->descriptionIcon($balanceResult['is_valid']
                    ? 'heroicon-m-check-circle'
                    : 'heroicon-m-exclamation-triangle'
                )
                ->color($balanceResult['is_valid'] ? 'success' : 'danger'),
        ];
    }

    private function getBankAccountsTotal(Company $company): float
    {
        return BankAccount::where('company_id', $company->id)->sum('current_balance');
    }

    private function getProcessorAccountsTotal(Company $company): float
    {
        return PaymentProcessorAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->sum(\DB::raw('current_balance + pending_balance'));
    }

    private function getPendingBalanceTotal(Company $company): float
    {
        return PaymentProcessorAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->sum('pending_balance');
    }

    private function formatMoney(float $amount): string
    {
        if ($amount >= 1000000) {
            return '$'.Number::format($amount / 1000000, 1).'M';
        } elseif ($amount >= 1000) {
            return '$'.Number::format($amount / 1000, 1).'K';
        } else {
            return '$'.Number::format($amount, 2);
        }
    }
}
