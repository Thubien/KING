<?php

namespace App\Filament\Resources\StoreCreditResource\Widgets;

use App\Models\StoreCredit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreCreditStats extends BaseWidget
{
    protected function getStats(): array
    {
        $activeCredits = StoreCredit::where('status', 'active')
            ->where('company_id', auth()->user()->company_id);
        
        $totalActive = $activeCredits->count();
        $totalActiveAmount = $activeCredits->sum('remaining_amount');
        
        $expiringCount = StoreCredit::where('status', 'active')
            ->where('company_id', auth()->user()->company_id)
            ->where('expires_at', '<=', now()->addDays(30))
            ->where('expires_at', '>', now())
            ->count();
        
        $usedThisMonth = StoreCredit::where('company_id', auth()->user()->company_id)
            ->whereMonth('last_used_at', now()->month)
            ->whereYear('last_used_at', now()->year)
            ->sum(\DB::raw('amount - remaining_amount'));

        return [
            Stat::make('Aktif Store Credit', $totalActive)
                ->description('Toplam aktif store credit sayısı')
                ->descriptionIcon('heroicon-m-gift')
                ->color('success'),
                
            Stat::make('Toplam Bakiye', number_format($totalActiveAmount, 2) . ' USD')
                ->description('Kullanılabilir toplam bakiye')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
                
            Stat::make('Süresi Dolmak Üzere', $expiringCount)
                ->description('30 gün içinde süresi dolacak')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Bu Ay Kullanılan', number_format($usedThisMonth, 2) . ' USD')
                ->description('Bu ay kullanılan store credit')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),
        ];
    }
}