<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryItemResource\Pages;
use App\Models\InventoryItem;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Inventory';

    protected static ?string $navigationGroup = 'Business Management';

    protected static ?string $modelLabel = 'Inventory Item';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Item Information')
                    ->schema([
                        Forms\Components\Select::make('store_id')
                            ->label('Store')
                            ->options(Store::where('company_id', Auth::user()->company_id)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $store = Store::find($state);
                                    $set('currency', $store->currency);
                                }
                            }),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('PROD-001'),

                                Forms\Components\TextInput::make('name')
                                    ->label('Item Name')
                                    ->required()
                                    ->placeholder('Product Name'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('category')
                                    ->label('Category')
                                    ->options([
                                        'electronics' => 'Electronics',
                                        'clothing' => 'Clothing',
                                        'accessories' => 'Accessories',
                                        'home' => 'Home & Garden',
                                        'beauty' => 'Beauty & Health',
                                        'toys' => 'Toys & Games',
                                        'sports' => 'Sports & Outdoors',
                                        'books' => 'Books & Media',
                                        'food' => 'Food & Beverage',
                                        'other' => 'Other',
                                    ])
                                    ->searchable(),

                                Forms\Components\TextInput::make('supplier')
                                    ->label('Supplier')
                                    ->placeholder('Supplier name'),
                            ]),
                    ]),

                Forms\Components\Section::make('Stock & Pricing')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Current Quantity')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0),

                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Unit Cost')
                                    ->numeric()
                                    ->required()
                                    ->prefix(fn ($get) => $get('currency') ?? 'USD')
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set, $get) => $set('total_value', ($state ?? 0) * ($get('quantity') ?? 0))
                                    ),

                                Forms\Components\TextInput::make('total_value')
                                    ->label('Total Value')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefix(fn ($get) => $get('currency') ?? 'USD'),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('reorder_point')
                                    ->label('Reorder Point')
                                    ->numeric()
                                    ->default(10)
                                    ->helperText('Alert when quantity falls below this'),

                                Forms\Components\TextInput::make('reorder_quantity')
                                    ->label('Reorder Quantity')
                                    ->numeric()
                                    ->default(50)
                                    ->helperText('Quantity to order when restocking'),

                                Forms\Components\TextInput::make('location')
                                    ->label('Storage Location')
                                    ->placeholder('Warehouse A, Shelf 3'),
                            ]),

                        Forms\Components\Hidden::make('currency'),
                    ]),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive items won\'t be included in inventory value'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Item Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->description ? \Str::limit($record->description, 50) : null),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Stock')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(function ($record) {
                        if ($record->quantity <= 0) {
                            return 'danger';
                        }
                        if ($record->quantity <= $record->reorder_point) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money(fn ($record) => $record->currency)
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money(fn ($record) => $record->currency)
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_restocked_at')
                    ->label('Last Restocked')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('Store')
                    ->relationship('store', 'name'),

                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'electronics' => 'Electronics',
                        'clothing' => 'Clothing',
                        'accessories' => 'Accessories',
                        'home' => 'Home & Garden',
                        'beauty' => 'Beauty & Health',
                        'toys' => 'Toys & Games',
                        'sports' => 'Sports & Outdoors',
                        'books' => 'Books & Media',
                        'food' => 'Food & Beverage',
                        'other' => 'Other',
                    ]),

                Tables\Filters\TernaryFilter::make('low_stock')
                    ->label('Low Stock')
                    ->queries(
                        true: fn ($query) => $query->whereRaw('quantity <= reorder_point'),
                        false: fn ($query) => $query->whereRaw('quantity > reorder_point'),
                    ),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('adjustment')
                            ->label('Adjustment Quantity')
                            ->numeric()
                            ->required()
                            ->helperText('Positive to add, negative to remove'),

                        Forms\Components\Select::make('reason')
                            ->label('Reason')
                            ->options([
                                'restock' => 'Restock',
                                'sale' => 'Sale',
                                'damage' => 'Damage/Loss',
                                'count' => 'Physical Count',
                                'return' => 'Customer Return',
                                'other' => 'Other',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->adjustQuantity(
                            $data['adjustment'],
                            $data['reason'].($data['notes'] ? ': '.$data['notes'] : '')
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Stock adjusted successfully')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
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
            'index' => Pages\ListInventoryItems::route('/'),
            'create' => Pages\CreateInventoryItem::route('/create'),
            'view' => Pages\ViewInventoryItem::route('/{record}'),
            'edit' => Pages\EditInventoryItem::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('store', fn ($query) => $query->where('company_id', Auth::user()->company_id)
            );
    }

    public static function getNavigationBadge(): ?string
    {
        $lowStockCount = static::getModel()::whereHas('store', fn ($query) => $query->where('company_id', Auth::user()->company_id)
        )
            ->where('is_active', true)
            ->whereRaw('quantity <= reorder_point')
            ->count();

        return $lowStockCount > 0 ? $lowStockCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
