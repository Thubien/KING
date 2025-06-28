<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = '💰 Recent Transactions';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        $company = Auth::user()->company;
        
        return $table
            ->query(
                Transaction::query()
                    ->where('company_id', $company->id)
                    ->with(['store', 'salesRep'])
                    ->orderBy('transaction_date', 'desc')
                    ->limit(15)
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('description')
                            ->weight('bold')
                            ->limit(40)
                            ->searchable(),
                            
                        Tables\Columns\TextColumn::make('amount_usd')
                            ->money('USD')
                            ->sortable()
                            ->alignEnd()
                            ->color('success'),
                    ]),
                    
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('sales_channel')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'shopify' => 'success',
                                    'instagram' => 'warning',
                                    'telegram' => 'info',
                                    'whatsapp' => 'success',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'shopify' => '🛒 Shopify',
                                    'instagram' => '📸 Instagram',
                                    'telegram' => '✈️ Telegram',
                                    'whatsapp' => '💬 WhatsApp',
                                    'facebook' => '📘 Facebook',
                                    'physical' => '🏪 Physical',
                                    'referral' => '🤝 Referral',
                                    default => '📦 Other',
                                }),
                                
                            Tables\Columns\TextColumn::make('payment_method')
                                ->badge()
                                ->color('gray')
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'cash' => '💵 Cash',
                                    'credit_card' => '💳 Credit Card',
                                    'bank_transfer' => '🏦 Bank Transfer',
                                    'cash_on_delivery' => '📦 COD',
                                    'cargo_collect' => '🚚 Cargo Collect',
                                    'crypto' => '₿ Crypto',
                                    'installment' => '📅 Installment',
                                    'store_credit' => '🎫 Store Credit',
                                    default => '💰 Other',
                                }),
                        ]),
                        
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('store.name')
                                ->label('Store')
                                ->icon('heroicon-o-building-storefront')
                                ->color('info')
                                ->limit(20),
                                
                            Tables\Columns\TextColumn::make('salesRep.name')
                                ->label('Sales Rep')
                                ->icon('heroicon-o-user')
                                ->color('purple')
                                ->limit(20)
                                ->placeholder('No sales rep'),
                        ])->alignEnd(),
                    ]),
                    
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('transaction_date')
                            ->dateTime('M j, Y g:i A')
                            ->color('gray')
                            ->icon('heroicon-o-calendar'),
                            
                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'completed' => 'success',
                                'processing' => 'warning',
                                'pending' => 'gray',
                                'failed' => 'danger',
                                'refunded' => 'danger',
                                default => 'gray',
                            })
                            ->alignEnd(),
                    ]),
                ])
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Transaction $record) => route('filament.admin.resources.transactions.view', $record)),
                    
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->url(fn (Transaction $record) => route('filament.admin.resources.transactions.edit', $record))
                        ->visible(fn (Transaction $record) => $record->data_source === 'manual_entry'),
                        
                    Tables\Actions\Action::make('mark_completed')
                        ->label('Mark Completed')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn (Transaction $record) => $record->update(['status' => 'completed']))
                        ->visible(fn (Transaction $record) => $record->status === 'processing'),
                        
                    Tables\Actions\Action::make('view_store')
                        ->label('View Store')
                        ->icon('heroicon-o-building-storefront')
                        ->url(fn (Transaction $record) => route('filament.admin.resources.stores.view', $record->store))
                        ->visible(fn (Transaction $record) => $record->store),
                ])
            ])
            ->emptyStateHeading('No Transactions Yet')
            ->emptyStateDescription('Transactions will appear here once you connect stores or add manual orders.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->paginated(false);
    }
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user?->isCompanyOwner() || $user?->isAdmin() || $user?->isSalesRep();
    }
}