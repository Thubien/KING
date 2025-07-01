<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class PerformanceChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Aylık Performans Trendi';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getData(): array
    {
        $user = auth()->user();
        $company = $user->company;

        // Get last 12 months trend
        $revenueData = Trend::model(Transaction::class)
            ->where('company_id', $company->id)
            ->where('type', 'INCOME')
            ->between(
                start: now()->subMonths(11)->startOfMonth(),
                end: now()->endOfMonth(),
            )
            ->perMonth()
            ->sum('amount_usd');

        $expenseData = Trend::model(Transaction::class)
            ->where('company_id', $company->id)
            ->where('type', 'EXPENSE')
            ->between(
                start: now()->subMonths(11)->startOfMonth(),
                end: now()->endOfMonth(),
            )
            ->perMonth()
            ->sum('amount_usd');

        return [
            'datasets' => [
                [
                    'label' => 'Gelir',
                    'data' => $revenueData->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Gider',
                    'data' => $expenseData->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $revenueData->map(fn (TrendValue $value) => $value->date->format('M Y')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
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
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'elements' => [
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 6,
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->company;
    }
} 