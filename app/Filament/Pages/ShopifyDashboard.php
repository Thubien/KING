<?php

namespace App\Filament\Pages;

use App\Models\Store;
use App\Models\Transaction;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ShopifyDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    
    protected static string $view = 'filament.pages.shopify-dashboard';
    
    protected static ?string $navigationLabel = 'Shopify Stores';
    
    protected static ?string $title = 'Shopify Store Management';
    
    protected static ?int $navigationSort = 3;
    
    public function getHeaderWidgets(): array
    {
        return [
            // Temporarily disabled
            // \App\Filament\Widgets\ShopifyStatsWidget::class,
            // \App\Filament\Widgets\ShopifyConnectionWidget::class,
        ];
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
            // Temporarily disabled
            // \App\Filament\Widgets\ShopifyStoreListWidget::class,
        ];
    }
}