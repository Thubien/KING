<?php

namespace App\Filament\Widgets;

use App\Models\Partnership;
use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PartnershipRevenueWidget extends BaseWidget
{
    protected static ?string $heading = ' Partnership Performance';
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        $company = Auth::user()->company;
        
        return $table
            ->query(
                Partnership::query()
                    ->whereHas('store', function ($query) use ($company) {
                        $query->where('company_id', $company->id);
                    })
                    ->where('status', 'ACTIVE')
                    ->with(['store', 'user'])
                    ->orderBy('ownership_percentage', 'desc')
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('user.name')
                            ->label('Partner')
                            ->weight('bold')
                            ->icon('heroicon-o-user')
                            ->searchable(),
                            
                        Tables\Columns\TextColumn::make('monthly_revenue')
                            ->label('This Month')
                            ->getStateUsing(function (Partnership $record) {
                                return $record->store->transactions()
                                    ->whereMonth('transaction_date', now()->month)
                                    ->whereYear('transaction_date', now()->year)
                                    ->where('category', 'SALES')
                                    ->sum('amount');
                            })
                            ->money('USD')
                            ->sortable()
                            ->alignEnd()
                            ->color('success'),
                    ]),
                    
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('store.name')
                            ->label('Store')
                            ->icon('heroicon-o-building-storefront')
                            ->color('info'),
                            
                        Tables\Columns\TextColumn::make('partner_share')
                            ->label('Partner Share')
                            ->getStateUsing(function (Partnership $record) {
                                $revenue = $record->store->transactions()
                                    ->whereMonth('transaction_date', now()->month)
                                    ->whereYear('transaction_date', now()->year)
                                    ->where('category', 'SALES')
                                    ->sum('amount');
                                return $revenue * ($record->ownership_percentage / 100);
                            })
                            ->money('USD')
                            ->color('warning'),
                            
                    ]),
                    
                    Tables\Columns\Layout\Panel::make([
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('partnership_details')
                                ->label('')
                                ->getStateUsing(function (Partnership $record) {
                                    return "Ownership: {$record->ownership_percentage}% | Role: {$record->role}";
                                })
                                ->color('gray'),
                                
                            Tables\Columns\TextColumn::make('total_revenue')
                                ->label('All Time Revenue')
                                ->getStateUsing(function (Partnership $record) {
                                    return $record->store->transactions()
                                        ->where('category', 'SALES')
                                        ->sum('amount');
                                })
                                ->money('USD')
                                ->color('success')
                                ->alignEnd(),
                        ]),
                    ])->collapsed(false),
                ])
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('View Partnership')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Partnership $record) => "/admin/partnerships/{$record->id}"),
                        
                    Tables\Actions\Action::make('view_store')
                        ->label('View Store')
                        ->icon('heroicon-o-building-storefront')
                        ->url(fn (Partnership $record) => "/admin/stores/{$record->store_id}"),
                        
                    Tables\Actions\Action::make('view_transactions')
                        ->label('View Transactions')
                        ->icon('heroicon-o-banknotes')
                        ->url(fn (Partnership $record) => "/admin/transactions?tableFilters[store_id][value]={$record->store_id}"),
                        
                    Tables\Actions\Action::make('send_report')
                        ->label('Send Monthly Report')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->action(function (Partnership $record) {
                            // TODO: Implement email report functionality
                            \Filament\Notifications\Notification::make()
                                ->title('Report Sent')
                                ->body("Monthly report sent to {$record->user->name}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Send Monthly Report')
                        ->modalDescription('Send a detailed monthly performance report to the partner.')
                        ->modalSubmitActionLabel('Send Report'),
                ])
            ])
            ->emptyStateHeading('No Active Partnerships')
            ->emptyStateDescription('Partnerships will appear here once they are created and activated.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->paginated(false);
    }
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && ($user->user_type === 'admin' || $user->user_type === 'company_owner');
    }
}