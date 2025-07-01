<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WelcomeWidget extends Widget
{
    protected static string $view = 'filament.widgets.welcome-widget';
    
    protected static ?int $sort = -10;
    
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        $user = auth()->user();
        
        // Only show for new users with no stores
        return $user && $user->company && $user->company->stores()->count() === 0;
    }
}