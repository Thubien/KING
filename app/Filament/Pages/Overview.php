<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Overview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string $view = 'filament.pages.overview';

    protected static ?string $title = 'Business Overview';

    protected static ?string $navigationLabel = 'Overview';

    protected static ?int $navigationSort = 0;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->isCompanyOwner() || Auth::user()?->isAdmin();
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Quick Actions based on user role
        if (Auth::user()->isCompanyOwner() || Auth::user()->isAdmin()) {
            $actions[] = Action::make('add_transaction')
                ->label('Add Transaction')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(route('filament.admin.resources.transactions.create'));

            $actions[] = Action::make('invite_partner')
                ->label('Invite Partner')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->url(route('filament.admin.resources.partnerships.create'));

            $actions[] = Action::make('add_store')
                ->label('Add Store')
                ->icon('heroicon-o-building-storefront')
                ->color('info')
                ->url(route('filament.admin.resources.stores.create'));
        }

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }
}
