<?php

namespace App\Filament\Widgets;

use App\Models\Partnership;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PartnerOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        $pendingInvitations = Partnership::whereHas('store', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        })->where('status', 'PENDING_INVITATION')->count();

        $activePartners = Partnership::whereHas('store', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        })->where('status', 'ACTIVE')->count();

        $expiredInvitations = Partnership::whereHas('store', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        })
            ->where('status', 'PENDING_INVITATION')
            ->where('invited_at', '<=', now()->subDays(7))
            ->count();

        return [
            Stat::make('Active Partners', $activePartners)
                ->description('Currently active partnerships')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Pending Invitations', $pendingInvitations)
                ->description('Awaiting partner response')
                ->descriptionIcon('heroicon-m-envelope')
                ->color($pendingInvitations > 0 ? 'warning' : 'gray'),

            Stat::make('Expired Invitations', $expiredInvitations)
                ->description('Invitations older than 7 days')
                ->descriptionIcon('heroicon-m-clock')
                ->color($expiredInvitations > 0 ? 'danger' : 'gray'),
        ];
    }

    protected static ?int $sort = 3;

    public function getColumns(): int
    {
        return 3;
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->isCompanyOwner() || $user->isAdmin());
    }
}
