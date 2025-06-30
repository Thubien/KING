<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class FinancialOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $cacheKey = "financial_overview:{$user->company_id}";

        $stats = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            $storeIds = $user->getAccessibleStoreIds();

            // This month's revenue
            $thisMonthRevenue = Transaction::whereIn('store_id', $storeIds)
                ->thisMonth()
                ->where('category', 'SALES')
                ->sum('amount');

            // Last month's revenue for comparison
            $lastMonthRevenue = Transaction::whereIn('store_id', $storeIds)
                ->whereMonth('transaction_date', now()->subMonth()->month)
                ->whereYear('transaction_date', now()->subMonth()->year)
                ->where('category', 'SALES')
                ->sum('amount');

            // This month's expenses
            $thisMonthExpenses = Transaction::whereIn('store_id', $storeIds)
                ->thisMonth()
                ->whereNotIn('category', ['SALES', 'RETURNS'])
                ->sum('amount');

            // Net profit
            $netProfit = $thisMonthRevenue - abs($thisMonthExpenses);

            // Growth calculation
            $growth = $lastMonthRevenue > 0
                ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
                : 0;

            return compact('thisMonthRevenue', 'thisMonthExpenses', 'netProfit', 'growth');
        });

        extract($stats);

        return [
            Stat::make('Monthly Revenue', '$'.number_format($thisMonthRevenue, 0))
                ->description(
                    $growth > 0
                        ? "↗ +{$growth}% from last month"
                        : ($growth < 0 ? "↘ {$growth}% from last month" : ' Same as last month')
                )
                ->descriptionIcon($growth > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($growth > 0 ? 'success' : ($growth < 0 ? 'danger' : 'gray')),

            Stat::make('Monthly Expenses', '$'.number_format(abs($thisMonthExpenses), 0))
                ->description('Total outgoing funds')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->color('warning'),

            Stat::make('Net Profit', '$'.number_format($netProfit, 0))
                ->description($netProfit > 0 ? 'Profitable month' : 'Operating at loss')
                ->descriptionIcon($netProfit > 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($netProfit > 0 ? 'success' : 'danger'),
        ];
    }

    protected static ?int $sort = 1;

    public function getColumns(): int
    {
        return 3;
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->isCompanyOwner() || $user->isAdmin() || $user->isPartner());
    }
}
