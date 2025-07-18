<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AnalyticsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.analytics-dashboard';

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?string $title = 'Business Analytics & Performance';

    protected static ?string $navigationGroup = 'Dashboard & Analytics';

    protected static ?int $navigationSort = 2;

    public function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isCompanyOwner() || $user?->isAdmin();
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\CompanyPerformanceWidget::class,
            \App\Filament\Widgets\SalesChannelBreakdownWidget::class,
            \App\Filament\Widgets\TopPerformersWidget::class,
            \App\Filament\Widgets\RecentTransactionsWidget::class,
        ];
    }
}
