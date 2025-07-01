<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Company Overview Dashboard';

    protected static ?string $navigationGroup = 'Dashboard & Analytics';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user?->isOwner() || $user?->isSuperAdmin();
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