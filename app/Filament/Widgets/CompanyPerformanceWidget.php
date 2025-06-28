<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Store;
use App\Models\Partnership;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CompanyPerformanceWidget extends ChartWidget
{
    protected static ?string $heading = '📊 Revenue Trends (Last 6 Months)';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    public ?string $filter = 'revenue';
    
    protected function getData(): array
    {
        $user = Auth::user();
        $company = $user->company;
        
        // Get last 6 months
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i));
        }
        
        $labels = $months->map(fn($month) => $month->format('M Y'))->toArray();
        
        if ($this->filter === 'revenue') {
            // Revenue by month
            $revenueData = $months->map(function ($month) use ($company) {
                return Transaction::where('company_id', $company->id)
                    ->whereMonth('transaction_date', $month->month)
                    ->whereYear('transaction_date', $month->year)
                    ->sum('amount_usd');
            })->toArray();
            
            // Revenue by sales channel
            $shopifyData = $months->map(function ($month) use ($company) {
                return Transaction::where('company_id', $company->id)
                    ->where('sales_channel', 'shopify')
                    ->whereMonth('transaction_date', $month->month)
                    ->whereYear('transaction_date', $month->year)
                    ->sum('amount_usd');
            })->toArray();
            
            $instagramData = $months->map(function ($month) use ($company) {
                return Transaction::where('company_id', $company->id)
                    ->where('sales_channel', 'instagram')
                    ->whereMonth('transaction_date', $month->month)
                    ->whereYear('transaction_date', $month->year)
                    ->sum('amount_usd');
            })->toArray();
            
            return [
                'datasets' => [
                    [
                        'label' => 'Total Revenue',
                        'data' => $revenueData,
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'borderColor' => 'rgb(59, 130, 246)',
                        'borderWidth' => 2,
                        'fill' => true,
                    ],
                    [
                        'label' => 'Shopify Revenue',
                        'data' => $shopifyData,
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'borderColor' => 'rgb(34, 197, 94)',
                        'borderWidth' => 2,
                        'fill' => false,
                    ],
                    [
                        'label' => 'Instagram Revenue',
                        'data' => $instagramData,
                        'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                        'borderColor' => 'rgb(168, 85, 247)',
                        'borderWidth' => 2,
                        'fill' => false,
                    ],
                ],
                'labels' => $labels,
            ];
        } else {
            // Orders by month
            $ordersData = $months->map(function ($month) use ($company) {
                return Transaction::where('company_id', $company->id)
                    ->whereMonth('transaction_date', $month->month)
                    ->whereYear('transaction_date', $month->year)
                    ->count();
            })->toArray();
            
            return [
                'datasets' => [
                    [
                        'label' => 'Total Orders',
                        'data' => $ordersData,
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                        'borderColor' => 'rgb(239, 68, 68)',
                        'borderWidth' => 2,
                        'fill' => true,
                    ],
                ],
                'labels' => $labels,
            ];
        }
    }
    
    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getFilters(): ?array
    {
        return [
            'revenue' => 'Revenue Trends',
            'orders' => 'Order Volume',
        ];
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value.toLocaleString(); }',
                    ],
                ],
            ],
        ];
    }
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user?->isCompanyOwner() || $user?->isAdmin();
    }
}