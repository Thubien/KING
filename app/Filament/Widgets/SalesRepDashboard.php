<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use Illuminate\Support\Number;

class SalesRepDashboard extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        
        // Only show for sales reps or admins
        if (!$user->canAccessSalesRepDashboard()) {
            return [];
        }

        // If admin/owner, show company-wide sales rep stats
        if ($user->isAdmin() || $user->isCompanyOwner()) {
            return $this->getCompanyStats();
        }

        // Show individual sales rep stats
        return $this->getIndividualStats($user);
    }

    protected function getIndividualStats(User $user): array
    {
        $stats = $user->getSalesRepStats();
        $customerStats = $user->getCustomerStats();
        
        return [
            Stat::make(' Monthly Sales', Number::currency($stats['current_month_sales'], 'USD'))
                ->description($stats['growth_percentage'] >= 0 
                    ? "+{$stats['growth_percentage']}% from last month" 
                    : "{$stats['growth_percentage']}% from last month")
                ->descriptionIcon($stats['growth_percentage'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($stats['growth_percentage'] >= 0 ? 'success' : 'danger'),

            Stat::make(' Commission Earned', Number::currency($stats['commission_earned'], 'USD'))
                ->description('Based on your partnership %')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make(' Total Orders', $stats['total_orders'])
                ->description('Orders created this month')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make(' Avg Order Value', Number::currency($stats['avg_order_value'], 'USD'))
                ->description('Average value per order')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Total Customers', $customerStats['total_customers'])
                ->description("{$customerStats['repeat_rate']}% repeat rate")
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),

            Stat::make(' Repeat Customers', $customerStats['repeat_customers'])
                ->description('Customers with multiple orders')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('success'),
        ];
    }

    protected function getCompanyStats(): array
    {
        $company = auth()->user()->company;
        
        // Get all sales reps in company
        $salesReps = User::whereHas('roles', function($query) {
                $query->where('name', 'sales_rep');
            })
            ->where('company_id', $company->id)
            ->get();

        $totalMonthlySales = $salesReps->sum(fn($rep) => $rep->getMonthlySales());
        $totalOrders = $salesReps->sum(fn($rep) => $rep->getSalesRepStats()['total_orders']);
        $avgOrderValue = $totalOrders > 0 ? $totalMonthlySales / $totalOrders : 0;
        
        // Calculate total commission owed
        $totalCommission = $salesReps->sum(fn($rep) => $rep->getMonthlyCommission());
        
        // Top performing sales rep
        $topRep = $salesReps->sortByDesc(fn($rep) => $rep->getMonthlySales())->first();
        
        return [
            Stat::make('🏢 Company Sales', Number::currency($totalMonthlySales, 'USD'))
                ->description('Total manual sales this month')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make(' Total Commissions', Number::currency($totalCommission, 'USD'))
                ->description('Total commission owed to sales reps')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make(' Total Manual Orders', $totalOrders)
                ->description('All manual orders this month')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make(' Avg Order Value', Number::currency($avgOrderValue, 'USD'))
                ->description('Company-wide average')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('👑 Top Performer', $topRep?->name ?? 'No sales yet')
                ->description($topRep ? Number::currency($topRep->getMonthlySales(), 'USD') . ' this month' : 'Waiting for first sale')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),

            Stat::make('Active Sales Reps', $salesReps->count())
                ->description('Total sales representatives')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->canAccessSalesRepDashboard() ?? false;
    }

    protected function getColumns(): int
    {
        return 3; // 3 columns layout
    }
}