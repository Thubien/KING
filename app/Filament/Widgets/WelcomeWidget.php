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
        
        // Show to all authenticated users with a company
        return $user && $user->company;
    }
}