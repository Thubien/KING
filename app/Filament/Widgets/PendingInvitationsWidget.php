<?php

namespace App\Filament\Widgets;

use App\Models\Partnership;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class PendingInvitationsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $cacheKey = 'widget:pending_invitations:' . auth()->user()->company_id;
        
        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $pendingInvitations = Partnership::pendingInvitation()->count();
            $expiredInvitations = Partnership::pendingInvitation()
                ->where('invited_at', '<=', now()->subDays(7))
                ->count();
            $recentInvitations = Partnership::pendingInvitation()
                ->where('invited_at', '>=', now()->subDays(3))
                ->count();
                
            return compact('pendingInvitations', 'expiredInvitations', 'recentInvitations');
        });
        
        extract($stats);

        return [
            Stat::make('Pending Invitations', $pendingInvitations)
                ->description('Total pending partner invitations')
                ->descriptionIcon('heroicon-m-envelope')
                ->color($pendingInvitations > 0 ? 'warning' : 'success'),

            Stat::make('Expired Invitations', $expiredInvitations)
                ->description('Invitations older than 7 days')
                ->descriptionIcon('heroicon-m-clock')
                ->color($expiredInvitations > 0 ? 'danger' : 'success'),

            Stat::make('Recent Invitations', $recentInvitations)
                ->description('Sent in last 3 days')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('info'),
        ];
    }

    protected static ?int $sort = 2;

    public function getColumns(): int
    {
        return 3;
    }
}