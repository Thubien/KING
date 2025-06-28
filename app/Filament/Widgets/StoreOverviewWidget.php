<?php

namespace App\Filament\Widgets;

use App\Models\Store;
use App\Models\Partnership;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        
        $totalStores = $user->company->stores()->count();
        $activeStores = $user->company->stores()->where('status', 'active')->count();
        $totalPartnerships = Partnership::whereHas('store', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        })->where('status', 'ACTIVE')->count();

        return [
            Stat::make('Total Stores', $totalStores)
                ->description('Connected store locations')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),

            Stat::make('Active Stores', $activeStores)
                ->description("$activeStores of $totalStores stores active")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($activeStores === $totalStores ? 'success' : 'warning'),

            Stat::make('Active Partnerships', $totalPartnerships)
                ->description('Total active partnerships')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }

    protected static ?int $sort = 2;

    public function getColumns(): int
    {
        return 3;
    }
}