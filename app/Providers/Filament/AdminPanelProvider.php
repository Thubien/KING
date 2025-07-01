<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RoleDashboardRedirect;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration()
            ->emailVerification()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->darkMode(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\WelcomeWidget::class,
                \App\Filament\Widgets\BalanceOverviewWidget::class,
                \App\Filament\Widgets\StoreOverviewWidget::class,
                \App\Filament\Widgets\RecentTransactionsWidget::class,
                \App\Filament\Widgets\PartnershipRevenueWidget::class,
                \App\Filament\Widgets\FinancialOverviewWidget::class,
                \App\Filament\Widgets\PartnerOverviewWidget::class,
                \App\Filament\Widgets\RecentActivityWidget::class,
                // Partner-specific widgets
                \App\Filament\Widgets\PartnerStatsWidget::class,
                \App\Filament\Widgets\PartnerDebtWidget::class,
                \App\Filament\Widgets\PartnerProfitShareWidget::class,
                \App\Filament\Widgets\PartnerStoresWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                // PREMIUM SAAS NAVIGATION GROUPS
                NavigationGroup::make('Dashboard & Analytics')
                    ->collapsed(false), // Always expanded for quick access
                    
                NavigationGroup::make('Sales & Orders')
                    ->collapsed(fn () => !auth()->user()?->isStaff()), // Expanded for staff
                    
                NavigationGroup::make('Financial Management')
                    ->collapsed(fn () => auth()->user()?->isStaff()), // Collapsed for staff
                    
                NavigationGroup::make('Business Management')
                    ->collapsed(fn () => !auth()->user()?->isOwner()), // Expanded for owners
                    
                NavigationGroup::make('Customer Relations')
                    ->collapsed(fn () => !auth()->user()?->canCreateOrders()), // Expanded for order creators
                    
                NavigationGroup::make('System & Analytics')
                    ->collapsed(fn () => !auth()->user()?->isSuperAdmin()), // Expanded for admins
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Ayarlarım')
                    ->url(fn (): string => route('filament.admin.pages.user-settings'))
                    ->icon('heroicon-o-cog-6-tooth'),
            ]);
    }
}
