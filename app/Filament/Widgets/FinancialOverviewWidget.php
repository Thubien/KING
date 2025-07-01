<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $user = auth()->user();
        $company = $user->company;

        // This month data
        $thisMonth = Transaction::where('company_id', $company->id)
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year);

        // Last month data for comparison
        $lastMonth = Transaction::where('company_id', $company->id)
            ->whereMonth('transaction_date', now()->subMonth()->month)
            ->whereYear('transaction_date', now()->subMonth()->year);

        $thisMonthRevenue = $thisMonth->where('type', 'INCOME')->sum('amount_usd');
        $lastMonthRevenue = $lastMonth->where('type', 'INCOME')->sum('amount_usd');
        $revenueChange = $lastMonthRevenue > 0 ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        $thisMonthExpenses = $thisMonth->where('type', 'EXPENSE')->sum('amount_usd');
        $lastMonthExpenses = $lastMonth->where('type', 'EXPENSE')->sum('amount_usd');
        $expensesChange = $lastMonthExpenses > 0 ? (($thisMonthExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100 : 0;

        $thisMonthProfit = $thisMonthRevenue - $thisMonthExpenses;
        $lastMonthProfit = $lastMonthRevenue - $lastMonthExpenses;
        $profitChange = $lastMonthProfit > 0 ? (($thisMonthProfit - $lastMonthProfit) / $lastMonthProfit) * 100 : 0;

        $thisMonthTransactionCount = $thisMonth->count();
        $lastMonthTransactionCount = $lastMonth->count();
        $transactionChange = $lastMonthTransactionCount > 0 ? (($thisMonthTransactionCount - $lastMonthTransactionCount) / $lastMonthTransactionCount) * 100 : 0;

        return [
            Stat::make('Bu Ay Gelir', Number::currency($thisMonthRevenue, 'USD'))
                ->description(($revenueChange >= 0 ? '+' : '') . number_format($revenueChange, 1) . '% geçen aya göre')
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart([
                    $lastMonthRevenue / 1000,
                    $thisMonthRevenue / 1000,
                    ($thisMonthRevenue * 1.1) / 1000,
                    ($thisMonthRevenue * 1.05) / 1000,
                    ($thisMonthRevenue * 1.15) / 1000,
                    ($thisMonthRevenue * 1.08) / 1000,
                    ($thisMonthRevenue * 1.12) / 1000,
                ]),

            Stat::make('Bu Ay Gider', Number::currency($thisMonthExpenses, 'USD'))
                ->description(($expensesChange >= 0 ? '+' : '') . number_format($expensesChange, 1) . '% geçen aya göre')
                ->descriptionIcon($expensesChange <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($expensesChange <= 0 ? 'success' : 'warning')
                ->chart([
                    $lastMonthExpenses / 1000,
                    $thisMonthExpenses / 1000,
                    ($thisMonthExpenses * 0.9) / 1000,
                    ($thisMonthExpenses * 0.95) / 1000,
                    ($thisMonthExpenses * 0.85) / 1000,
                    ($thisMonthExpenses * 0.92) / 1000,
                    ($thisMonthExpenses * 0.88) / 1000,
                ]),

            Stat::make('Net Kâr', Number::currency($thisMonthProfit, 'USD'))
                ->description(($profitChange >= 0 ? '+' : '') . number_format($profitChange, 1) . '% geçen aya göre')
                ->descriptionIcon($profitChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($thisMonthProfit >= 0 ? 'success' : 'danger')
                ->chart([
                    $lastMonthProfit / 1000,
                    $thisMonthProfit / 1000,
                    ($thisMonthProfit * 1.2) / 1000,
                    ($thisMonthProfit * 1.1) / 1000,
                    ($thisMonthProfit * 1.25) / 1000,
                    ($thisMonthProfit * 1.15) / 1000,
                    ($thisMonthProfit * 1.18) / 1000,
                ]),

            Stat::make('Toplam İşlem', $thisMonthTransactionCount)
                ->description(($transactionChange >= 0 ? '+' : '') . number_format($transactionChange, 1) . '% geçen aya göre')
                ->descriptionIcon($transactionChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($transactionChange >= 0 ? 'info' : 'gray')
                ->chart([
                    $lastMonthTransactionCount,
                    $thisMonthTransactionCount,
                    $thisMonthTransactionCount * 1.1,
                    $thisMonthTransactionCount * 1.05,
                    $thisMonthTransactionCount * 1.15,
                    $thisMonthTransactionCount * 1.08,
                    $thisMonthTransactionCount * 1.12,
                ]),
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->company;
    }

    public function getColumns(): int
    {
        return 4;
    }
}
