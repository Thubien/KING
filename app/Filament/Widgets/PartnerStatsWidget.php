<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PartnerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()?->isPartner() ?? false;
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user->isPartner()) {
            return [];
        }

        $totalStores = $user->getActivePartnerships()->count();
        $totalOwnership = $user->getTotalOwnershipPercentage();
        $monthlyProfitShare = $user->getTotalMonthlyProfitShare();

        return [
            Stat::make('My Stores', $totalStores)
                ->description('Active partnerships')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('success'),

            Stat::make('Total Ownership', number_format($totalOwnership, 2).'%')
                ->description('Across all stores')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($totalOwnership > 50 ? 'success' : ($totalOwnership > 25 ? 'warning' : 'gray')),

            Stat::make('Monthly Profit', '$'.number_format($monthlyProfitShare, 2))
                ->description('This month\'s earnings')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
        ];
    }

    public function getColumns(): int
    {
        return 3;
    }
}
