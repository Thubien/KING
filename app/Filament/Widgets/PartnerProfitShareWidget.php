<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;

class PartnerProfitShareWidget extends ChartWidget
{
    protected static ?string $heading = 'My Profit Share - Last 6 Months';
    
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return Auth::user()?->isPartner() ?? false;
    }

    protected function getData(): array
    {
        $user = Auth::user();
        
        if (!$user->isPartner()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $months = [];
        $profitData = [];

        // Get last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $monthStart = $date->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            
            $totalProfit = 0;
            
            // Calculate profit for each partnership
            foreach ($user->getActivePartnerships() as $partnership) {
                $storeRevenue = Transaction::where('store_id', $partnership->store_id)
                    ->where('category', 'SALES')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->sum('amount');
                
                $partnerProfit = $storeRevenue * ($partnership->ownership_percentage / 100);
                $totalProfit += $partnerProfit;
            }
            
            $profitData[] = round($totalProfit, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Profit Share ($)',
                    'data' => $profitData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'pointBackgroundColor' => 'rgb(59, 130, 246)',
                    'pointBorderColor' => '#fff',
                    'pointHoverBackgroundColor' => '#fff',
                    'pointHoverBorderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value.toFixed(2); }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}