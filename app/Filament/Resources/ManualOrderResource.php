<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManualOrderResource\Pages;
use App\Models\Transaction;
use App\Traits\HasSimpleAuthorization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManualOrderResource extends Resource
{
    use HasSimpleAuthorization;

    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $modelLabel = 'Manual Order';

    protected static ?string $navigationLabel = 'Manual Orders';

    protected static ?string $navigationGroup = 'Sales Management';

    protected static ?int $navigationSort = 2;

    // SIMPLIFIED AUTHORIZATION - Staff can create orders
    protected static function getResourcePermissions(): array
    {
        return [
            'owner' => true,   // Owners can manage all orders
            'partner' => true, // Partners can view their store orders
            'staff' => true,   // Staff can create and manage orders
        ];
    }

    public static function canViewAny(): bool
    {
        return static::canAccessResource();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user?->isSuperAdmin() || $user?->isOwner() || $user?->isStaff();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccessResource();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Order Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('📋 Order Information')
                            ->schema([
                                Forms\Components\Section::make('Store & Channel')
                                    ->schema([
                                        Forms\Components\Select::make('store_id')
                                            ->relationship('store', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload(),

                                        Forms\Components\Select::make('sales_channel')
                                            ->options(Transaction::SALES_CHANNELS)
                                            ->required()
                                            ->default('instagram'),

                                        Forms\Components\Select::make('payment_method')
                                            ->options(Transaction::PAYMENT_METHODS)
                                            ->required()
                                            ->default('bank_transfer'),
                                    ])
                                    ->columns(3),

                                Forms\Components\Section::make('Order Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('description')
                                            ->label('Product/Service Description')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Instagram post ürünü - Beyaz elbise'),

                                        Forms\Components\TextInput::make('amount')
                                            ->label('Order Amount')
                                            ->required()
                                            ->numeric()
                                            ->prefix('₺')
                                            ->step(0.01),

                                        Forms\Components\Select::make('currency')
                                            ->options([
                                                'TRY' => '₺ Turkish Lira',
                                                'USD' => '$ US Dollar',
                                                'EUR' => '€ Euro',
                                            ])
                                            ->default('TRY')
                                            ->required(),

                                        Forms\Components\DateTimePicker::make('transaction_date')
                                            ->label('Sale Date')
                                            ->required()
                                            ->default(now()),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('👤 Customer Information')
                            ->schema([
                                Forms\Components\Section::make('Customer Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('customer_info.name')
                                            ->label('Customer Name')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('customer_info.phone')
                                            ->label('Phone Number')
                                            ->tel()
                                            ->placeholder('+90 5XX XXX XX XX'),

                                        Forms\Components\TextInput::make('customer_info.instagram_handle')
                                            ->label('Instagram Handle')
                                            ->prefix('@')
                                            ->placeholder('username'),

                                        Forms\Components\TextInput::make('customer_info.telegram_handle')
                                            ->label('Telegram Handle')
                                            ->prefix('@')
                                            ->placeholder('username'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Address')
                                    ->schema([
                                        Forms\Components\Textarea::make('customer_info.address')
                                            ->label('Delivery Address')
                                            ->rows(3)
                                            ->placeholder('Full delivery address...'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('📝 Additional Details')
                            ->schema([
                                Forms\Components\Section::make('Sales Rep & Commission')
                                    ->schema([
                                        Forms\Components\Select::make('sales_rep_id')
                                            ->label('Sales Representative')
                                            ->relationship('salesRep', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->default(auth()->id()),
                                    ]),

                                Forms\Components\Section::make('Order Notes')
                                    ->schema([
                                        Forms\Components\Textarea::make('order_notes')
                                            ->label('Order Notes')
                                            ->rows(3)
                                            ->placeholder('DM screenshots, special requests, etc.'),

                                        Forms\Components\TextInput::make('order_reference')
                                            ->label('Reference Link')
                                            ->url()
                                            ->placeholder('Instagram post link, Telegram message link, etc.'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Hidden fields with defaults
                Forms\Components\Hidden::make('type')->default('INCOME'),
                Forms\Components\Hidden::make('category')->default('SALES'),
                Forms\Components\Hidden::make('status')->default('APPROVED'),
                Forms\Components\Hidden::make('data_source')->default('manual_entry'),
                Forms\Components\Hidden::make('is_reconciled')->default(true),
                Forms\Components\Hidden::make('processed_at')->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Order ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('sales_channel')
                    ->label('Channel')
                    ->formatStateUsing(fn ($state) => Transaction::SALES_CHANNELS[$state] ?? $state)
                    ->colors([
                        'success' => 'shopify',
                        'warning' => 'instagram',
                        'info' => 'telegram',
                        'primary' => 'whatsapp',
                        'secondary' => fn ($state) => in_array($state, ['facebook', 'physical', 'referral', 'other']),
                    ]),

                Tables\Columns\TextColumn::make('customer_info.name')
                    ->label('Customer')
                    ->searchable()
                    ->getStateUsing(fn ($record) => $record->customer_info['name'] ?? 'N/A'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Product/Service')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('payment_method')
                    ->label('Payment')
                    ->formatStateUsing(fn ($state) => Transaction::PAYMENT_METHODS[$state] ?? $state)
                    ->colors([
                        'success' => 'cash',
                        'primary' => 'credit_card',
                        'warning' => 'bank_transfer',
                        'info' => fn ($state) => in_array($state, ['cash_on_delivery', 'cargo_collect']),
                        'secondary' => fn ($state) => in_array($state, ['crypto', 'installment', 'store_credit', 'other']),
                    ]),

                Tables\Columns\TextColumn::make('salesRep.name')
                    ->label('Sales Rep')
                    ->searchable(),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sales_channel')
                    ->options(Transaction::SALES_CHANNELS),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->options(Transaction::PAYMENT_METHODS),

                Tables\Filters\SelectFilter::make('store_id')
                    ->relationship('store', 'name'),

                Tables\Filters\SelectFilter::make('sales_rep_id')
                    ->relationship('salesRep', 'name'),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('data_source', 'manual_entry')
            ->where('type', 'INCOME');

        $user = auth()->user();

        // Sales reps can only see their own orders
        if ($user->isSalesRep()) {
            $query->where('sales_rep_id', $user->id);
        }
        // Company owners and admins see all manual orders
        elseif ($user->isCompanyOwner() || $user->isAdmin()) {
            $query->whereHas('store', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }
        // Partners see orders from their stores
        elseif ($user->isPartner()) {
            $accessibleStoreIds = $user->getAccessibleStoreIds();
            $query->whereIn('store_id', $accessibleStoreIds);
        }

        return $query->orderBy('transaction_date', 'desc');
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
            'index' => Pages\ListManualOrders::route('/'),
            'create' => Pages\CreateManualOrder::route('/create'),
            'view' => Pages\ViewManualOrder::route('/{record}'),
            'edit' => Pages\EditManualOrder::route('/{record}/edit'),
        ];
    }
}
