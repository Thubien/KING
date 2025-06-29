<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\Widget;

class PartnerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.pages.partner-dashboard';
    
    protected static ?string $title = 'Partner Dashboard';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Auth::user()?->isPartner() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->isPartner() ?? false;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\PartnerStatsWidget::class,
            \App\Filament\Widgets\PartnerProfitShareWidget::class,
            \App\Filament\Widgets\PartnerStoresWidget::class,
        ];
    }

    public function mount(): void
    {
        if (!Auth::user()->isPartner()) {
            abort(403, 'Access denied. Partners only.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // Profile actions can be added here later
        ];
    }
}