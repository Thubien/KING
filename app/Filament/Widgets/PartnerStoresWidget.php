<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\Partnership;

class PartnerStoresWidget extends BaseWidget
{
    protected static ?string $heading = 'My Store Partnerships';
    
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return Auth::user()?->isPartner() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Partnership::query()
                    ->where('user_id', Auth::id())
                    ->where('status', 'ACTIVE')
                    ->with(['store'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store Name')
                    ->weight('medium')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('ownership_percentage')
                    ->label('Ownership')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                    ->badge()
                    ->color(fn ($state) => $state > 50 ? 'success' : ($state > 25 ? 'warning' : 'gray')),
                    
                Tables\Columns\BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'success' => 'owner',
                        'warning' => 'partner',
                        'info' => 'investor',
                        'gray' => 'manager',
                    ]),
                    
                Tables\Columns\TextColumn::make('partnership_start_date')
                    ->label('Since')
                    ->date('M j, Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('current_month_revenue')
                    ->label('This Month Revenue')
                    ->getStateUsing(function ($record) {
                        $currentMonth = now()->startOfMonth();
                        $nextMonth = now()->addMonth()->startOfMonth();
                        
                        $revenue = $record->store->transactions()
                            ->where('category', 'SALES')
                            ->whereBetween('created_at', [$currentMonth, $nextMonth])
                            ->sum('amount');
                            
                        return '$' . number_format($revenue, 2);
                    })
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('my_profit_share')
                    ->label('My Share')
                    ->getStateUsing(function ($record) {
                        $currentMonth = now()->startOfMonth();
                        $nextMonth = now()->addMonth()->startOfMonth();
                        
                        $revenue = $record->store->transactions()
                            ->where('category', 'SALES')
                            ->whereBetween('created_at', [$currentMonth, $nextMonth])
                            ->sum('amount');
                            
                        $myShare = $revenue * ($record->ownership_percentage / 100);
                        return '$' . number_format($myShare, 2);
                    })
                    ->weight('bold')
                    ->color('primary'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.admin.resources.stores.view', $record->store))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('No partnerships yet')
            ->emptyStateDescription('You don\'t have any active partnerships.')
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->paginated(false);
    }
}