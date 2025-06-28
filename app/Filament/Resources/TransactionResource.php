<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = '💰 Transactions';
    
    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', Transaction::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', Transaction::class) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isCompanyOwner() || auth()->user()?->isAdmin() || auth()->user()?->isPartner() || auth()->user()?->isSalesRep();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Company owners and admins see all transactions in their company
        if ($user->isCompanyOwner() || $user->isAdmin()) {
            return $query->where('company_id', $user->company_id);
        }

        // Sales reps see only their own transactions
        if ($user->isSalesRep()) {
            return $query->where('company_id', $user->company_id)
                         ->where('sales_rep_id', $user->id);
        }

        // Partners only see transactions from stores they have partnerships in
        if ($user->isPartner()) {
            $accessibleStoreIds = $user->getAccessibleStoreIds();
            return $query->whereIn('store_id', $accessibleStoreIds);
        }

        return $query->whereNull('id'); // Return empty for other user types
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('store_id')
                    ->relationship('store', 'name')
                    ->required(),
                Forms\Components\TextInput::make('created_by')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('transaction_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('external_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('reference_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->maxLength(3)
                    ->default('USD'),
                Forms\Components\TextInput::make('exchange_rate')
                    ->required()
                    ->numeric()
                    ->default(1.000000),
                Forms\Components\TextInput::make('amount_usd')
                    ->numeric(),
                Forms\Components\TextInput::make('category')
                    ->required(),
                Forms\Components\TextInput::make('subcategory')
                    ->maxLength(255),
                Forms\Components\TextInput::make('type')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('metadata'),
                Forms\Components\DateTimePicker::make('transaction_date')
                    ->required(),
                Forms\Components\DateTimePicker::make('processed_at'),
                Forms\Components\TextInput::make('source')
                    ->required(),
                Forms\Components\TextInput::make('source_details')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_reconciled')
                    ->required(),
                Forms\Components\DateTimePicker::make('reconciled_at'),
                Forms\Components\TextInput::make('reconciled_by')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(40)
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('amount_usd')
                    ->money('USD')
                    ->sortable()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('sales_channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'shopify' => 'success',
                        'instagram' => 'warning',
                        'telegram' => 'info',
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
                        'cargo_collect' => '🚚 Cargo',
                        'crypto' => '₿ Crypto',
                        'installment' => '📅 Installment',
                        'store_credit' => '🎫 Credit',
                        default => '💰 Other',
                    }),
                    
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->sortable()
                    ->limit(20),
                    
                Tables\Columns\TextColumn::make('salesRep.name')
                    ->label('Sales Rep')
                    ->placeholder('—')
                    ->limit(20),
                    
                Tables\Columns\TextColumn::make('transaction_date')
                    ->dateTime('M j, Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'pending' => 'gray',
                        'failed' => 'danger',
                        'refunded' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('data_source')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'shopify_api' => 'Shopify API',
                        'manual_entry' => 'Manual',
                        'csv_import' => 'CSV Import',
                        'webhook' => 'Webhook',
                        default => ucwords(str_replace('_', ' ', $state)),
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sales_channel')
                    ->options([
                        'shopify' => '🛒 Shopify',
                        'instagram' => '📸 Instagram',
                        'telegram' => '✈️ Telegram',
                        'whatsapp' => '💬 WhatsApp',
                        'facebook' => '📘 Facebook',
                        'physical' => '🏪 Physical',
                        'referral' => '🤝 Referral',
                        'other' => '📦 Other',
                    ]),
                    
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => '💵 Cash',
                        'credit_card' => '💳 Credit Card',
                        'bank_transfer' => '🏦 Bank Transfer',
                        'cash_on_delivery' => '📦 COD',
                        'cargo_collect' => '🚚 Cargo Collect',
                        'crypto' => '₿ Crypto',
                        'installment' => '📅 Installment',
                        'store_credit' => '🎫 Store Credit',
                        'other' => '💰 Other',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                    
                Tables\Filters\Filter::make('this_month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('transaction_date', now()->month))
                    ->default(),
                    
                Tables\Filters\Filter::make('this_year')
                    ->query(fn (Builder $query): Builder => $query->whereYear('transaction_date', now()->year)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->data_source === 'manual_entry'),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
