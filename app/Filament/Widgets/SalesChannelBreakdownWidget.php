<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SalesChannelBreakdownWidget extends ChartWidget
{
    protected static ?string $heading = 'Sales Channel Performance';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];
    
    public ?string $filter = 'this_month';
    
    protected function getData(): array
    {
        $user = Auth::user();
        $company = $user->company;
        
        $query = Transaction::where('company_id', $company->id);
        
        if ($this->filter === 'this_month') {
            $query->whereMonth('transaction_date', now()->month)
                  ->whereYear('transaction_date', now()->year);
        } elseif ($this->filter === 'last_month') {
            $query->whereMonth('transaction_date', now()->subMonth()->month)
                  ->whereYear('transaction_date', now()->subMonth()->year);
        } elseif ($this->filter === 'this_year') {
            $query->whereYear('transaction_date', now()->year);
        }
        
        $channelData = $query->selectRaw('sales_channel, SUM(amount_usd) as total')
            ->groupBy('sales_channel')
            ->orderBy('total', 'desc')
            ->get();
        
        $channels = $channelData->pluck('sales_channel')->toArray();
        $totals = $channelData->pluck('total')->toArray();
        
        // Map channel names to emojis
        $channelLabels = array_map(function($channel) {
            return match($channel) {
                'shopify' => 'Shopify',
                'instagram' => 'Instagram',
                'telegram' => 'Telegram',
                'whatsapp' => 'WhatsApp',
                'facebook' => 'Facebook',
                'physical' => 'Physical Store',
                'referral' => 'Referral',
                default => 'Other'
            };
        }, $channels);
        
        return [
            'datasets' => [
                [
                    'data' => $totals,
                    'backgroundColor' => [
                        '#10B981', // Shopify - Green
                        '#8B5CF6', // Instagram - Purple
                        '#06B6D4', // Telegram - Cyan
                        '#10B981', // WhatsApp - Green
                        '#3B82F6', // Facebook - Blue
                        '#F59E0B', // Physical - Amber
                        '#EF4444', // Referral - Red
                        '#6B7280', // Other - Gray
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $channelLabels,
        ];
    }
    
    protected function getType(): string
    {
        return 'doughnut';
    }
    
    protected function getFilters(): ?array
    {
        return [
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year' => 'This Year',
        ];
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.label + ": $" + context.parsed.toLocaleString(); }',
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