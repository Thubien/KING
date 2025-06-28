<?php

namespace App\Filament\Widgets;

use App\Models\Store;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class ShopifyStoreListWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Store::query()
                    ->where('company_id', Auth::user()->company_id)
                    ->where('status', 'active')
                    ->whereNotNull('shopify_domain')
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('name')
                                ->weight('bold')
                                ->searchable()
                                ->icon('heroicon-o-building-storefront'),
                                
                            Tables\Columns\TextColumn::make('shopify_domain')
                                ->color('gray')
                                ->size('sm'),
                        ]),
                        
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('currency')
                                ->badge()
                                ->color('primary'),
                                
                            Tables\Columns\TextColumn::make('last_sync_at')
                                ->label('Last Sync')
                                ->since()
                                ->color(fn (Store $record) => 
                                    $record->last_sync_at && $record->last_sync_at->diffInHours(now()) > 2 
                                        ? 'warning' 
                                        : 'success'
                                )
                                ->icon(fn (Store $record) => 
                                    $record->last_sync_at && $record->last_sync_at->diffInHours(now()) > 2 
                                        ? 'heroicon-o-exclamation-triangle' 
                                        : 'heroicon-o-check-circle'
                                ),
                        ])->alignEnd(),
                    ]),
                    
                    Tables\Columns\Layout\Panel::make([
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('partnerships_count')
                                ->counts('partnerships')
                                ->label('Partnerships')
                                ->icon('heroicon-o-user-group')
                                ->color('purple'),
                                
                            Tables\Columns\TextColumn::make('monthly_revenue')
                                ->label('This Month')
                                ->money('USD')
                                ->getStateUsing(function (Store $record) {
                                    return $record->transactions()
                                        ->whereMonth('transaction_date', now()->month)
                                        ->whereYear('transaction_date', now()->year)
                                        ->sum('amount_usd');
                                })
                                ->icon('heroicon-o-banknotes')
                                ->color('success'),
                                
                            Tables\Columns\TextColumn::make('total_orders')
                                ->label('Total Orders')
                                ->getStateUsing(function (Store $record) {
                                    return $record->transactions()->count();
                                })
                                ->icon('heroicon-o-shopping-bag')
                                ->color('info'),
                        ]),
                    ])->collapsed(false),
                ])
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Store $record) => route('filament.admin.resources.stores.view', $record)),
                        
                    Tables\Actions\Action::make('sync_now')
                        ->label('Sync Now')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function (Store $record) {
                            \App\Jobs\SyncShopifyStoreData::dispatch($record);
                            \Filament\Notifications\Notification::make()
                                ->title('Sync Started')
                                ->body("Data sync has been queued for {$record->name}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Sync Store Data')
                        ->modalDescription('This will sync orders from Shopify. The process runs in the background.')
                        ->modalSubmitActionLabel('Start Sync'),
                        
                    Tables\Actions\Action::make('partnerships')
                        ->label('Manage Partnerships')
                        ->icon('heroicon-o-user-group')
                        ->color('purple')
                        ->url(fn (Store $record) => route('filament.admin.resources.partnerships.index', ['tableFilters' => ['store_id' => ['value' => $record->id]]])),
                        
                    Tables\Actions\Action::make('disconnect')
                        ->label('Disconnect')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function (Store $record) {
                            $record->update([
                                'status' => 'disconnected',
                                'shopify_access_token' => null
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Store Disconnected')
                                ->body("Store '{$record->name}' has been disconnected. Transaction history is preserved.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Disconnect Store')
                        ->modalDescription('Are you sure you want to disconnect this store? Transaction history will be preserved, but no new data will sync.')
                        ->modalSubmitActionLabel('Disconnect')
                        ->visible(fn () => Auth::user()?->isCompanyOwner()),
                ])
            ])
            ->emptyStateHeading('No Shopify Stores Connected')
            ->emptyStateDescription('Connect your first Shopify store using the form above to start syncing orders and managing partnerships.')
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->paginated(false);
    }
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user?->isCompanyOwner() || $user?->isAdmin();
    }
}