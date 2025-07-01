<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Ana Sayfa';

    protected static ?string $title = 'İş Performansı Dashboard';

    protected static ?string $navigationGroup = 'Dashboard & Analytics';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user?->isOwner() || $user?->isSuperAdmin() || $user?->isCompanyOwner() || $user?->isAdmin();
    }

    public function getColumns(): int | string | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}