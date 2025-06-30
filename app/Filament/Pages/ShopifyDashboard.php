<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
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
            \App\Filament\Widgets\ShopifyStatsWidget::class,
            \App\Filament\Widgets\ShopifyConnectionWidget::class,
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
            \App\Filament\Widgets\ShopifyStoreListWidget::class,
        ];
    }
}
