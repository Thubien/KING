<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PaymentMethodBreakdownWidget extends ChartWidget
{
    protected static ?string $heading = 'Payment Method Distribution';
    
    protected static ?int $sort = 6;
    
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
        
        $paymentData = $query->selectRaw('payment_method, SUM(amount_usd) as total')
            ->groupBy('payment_method')
            ->orderBy('total', 'desc')
            ->get();
        
        $methods = $paymentData->pluck('payment_method')->toArray();
        $totals = $paymentData->pluck('total')->toArray();
        
        // Map payment method names to emojis
        $methodLabels = array_map(function($method) {
            return match($method) {
                'cash' => 'Cash',
                'credit_card' => 'Credit Card',
                'bank_transfer' => 'Bank Transfer',
                'cash_on_delivery' => 'Cash on Delivery',
                'cargo_collect' => 'Cargo Collect',
                'crypto' => 'Cryptocurrency',
                'installment' => 'Installment',
                'store_credit' => 'Store Credit',
                default => 'Other'
            };
        }, $methods);
        
        return [
            'datasets' => [
                [
                    'data' => $totals,
                    'backgroundColor' => [
                        '#22C55E', // Cash - Green
                        '#3B82F6', // Credit Card - Blue
                        '#8B5CF6', // Bank Transfer - Purple
                        '#F59E0B', // COD - Amber
                        '#EF4444', // Cargo Collect - Red
                        '#F97316', // Crypto - Orange
                        '#06B6D4', // Installment - Cyan
                        '#84CC16', // Store Credit - Lime
                        '#6B7280', // Other - Gray
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $methodLabels,
        ];
    }
    
    protected function getType(): string
    {
        return 'pie';
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