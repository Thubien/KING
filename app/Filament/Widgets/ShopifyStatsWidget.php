<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class ShopifyStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $company = $user->company;

        // Get Shopify stores
        $shopifyStores = $company->stores()
            ->where('status', 'active')
            ->whereNotNull('shopify_domain')
            ->get();

        // Get this month's transactions from Shopify
        $thisMonthShopify = Transaction::where('company_id', $company->id)
            ->where('sales_channel', 'shopify')
            ->where('data_source', 'shopify_api')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year);

        $thisMonthRevenue = $thisMonthShopify->sum('amount_usd');
        $thisMonthOrders = $thisMonthShopify->count();

        // Get last month for comparison
        $lastMonthShopify = Transaction::where('company_id', $company->id)
            ->where('sales_channel', 'shopify')
            ->where('data_source', 'shopify_api')
            ->whereMonth('transaction_date', now()->subMonth()->month)
            ->whereYear('transaction_date', now()->subMonth()->year);

        $lastMonthRevenue = $lastMonthShopify->sum('amount_usd');
        $lastMonthOrders = $lastMonthShopify->count();

        // Calculate growth
        $revenueGrowth = $lastMonthRevenue > 0
            ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        $ordersGrowth = $lastMonthOrders > 0
            ? (($thisMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100
            : 0;

        // Get sync status
        $storesNeedingSync = $shopifyStores->filter(function ($store) {
            return ! $store->last_sync_at || $store->last_sync_at->diffInHours(now()) > 2;
        })->count();

        return [
            Stat::make('Connected Stores', $shopifyStores->count())
                ->description($company->plan === 'starter' ? '3 max on Starter' : 'Upgrade for more stores')
                ->descriptionIcon($shopifyStores->count() >= 3 && $company->plan === 'starter' ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-arrow-trending-up')
                ->color($shopifyStores->count() >= 3 && $company->plan === 'starter' ? 'warning' : 'success'),

            Stat::make(' This Month Revenue', Number::currency($thisMonthRevenue, 'USD'))
                ->description($revenueGrowth >= 0 ? "+{$revenueGrowth}% from last month" : "{$revenueGrowth}% from last month")
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'danger'),

            Stat::make(' Orders This Month', number_format($thisMonthOrders))
                ->description($ordersGrowth >= 0 ? "+{$ordersGrowth}% from last month" : "{$ordersGrowth}% from last month")
                ->descriptionIcon($ordersGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($ordersGrowth >= 0 ? 'success' : 'danger'),

            Stat::make(' Sync Status', $storesNeedingSync === 0 ? 'All Up to Date' : "{$storesNeedingSync} Need Sync")
                ->description($storesNeedingSync === 0 ? 'Data synchronized' : 'Some stores need syncing')
                ->descriptionIcon($storesNeedingSync === 0 ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($storesNeedingSync === 0 ? 'success' : 'warning'),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user?->isCompanyOwner() || $user?->isAdmin();
    }
}
