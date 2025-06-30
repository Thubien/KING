<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreCreditResource\Pages;
use App\Filament\Resources\StoreCreditResource\RelationManagers;
use App\Models\StoreCredit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;

class StoreCreditResource extends Resource
{
    protected static ?string $model = StoreCredit::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    
    protected static ?string $navigationLabel = 'Store Credits';
    
    protected static ?string $modelLabel = 'Store Credit';
    
    protected static ?string $pluralModelLabel = 'Store Credits';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $navigationGroup = 'Sales & Orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Store Credit Bilgileri')
                    ->description('Store credit temel bilgileri')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('store_id')
                                    ->label('Mağaza')
                                    ->relationship('store', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                        $set('currency', $state ? \App\Models\Store::find($state)?->currency : null)
                                    ),
                                
                                Forms\Components\TextInput::make('code')
                                    ->label('Store Credit Kodu')
                                    ->default(fn () => 'SC-' . strtoupper(\Str::random(8)))
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->unique(ignoreRecord: true),
                            ]),
                        
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Tutar')
                                    ->numeric()
                                    ->required()
                                    ->prefix(fn (Forms\Get $get) => $get('currency') ?? 'USD')
                                    ->disabled(fn (?Model $record) => $record && $record->status !== 'active'),
                                
                                Forms\Components\TextInput::make('remaining_amount')
                                    ->label('Kalan Tutar')
                                    ->numeric()
                                    ->prefix(fn (Forms\Get $get) => $get('currency') ?? 'USD')
                                    ->disabled()
                                    ->dehydrated(false),
                                
                                Forms\Components\TextInput::make('currency')
                                    ->label('Para Birimi')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                            ]),
                    ]),
                
                Section::make('Müşteri Bilgileri')
                    ->description('Store credit sahibi müşteri bilgileri')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('customer_name')
                                    ->label('Müşteri Adı')
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('customer_email')
                                    ->label('E-posta')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('customer_phone')
                                    ->label('Telefon')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                    ]),
                
                Section::make('Geçerlilik ve Durum')
                    ->description('Store credit geçerlilik süresi ve durumu')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('issued_at')
                                    ->label('Düzenlenme Tarihi')
                                    ->default(now())
                                    ->disabled()
                                    ->dehydrated(),
                                
                                Forms\Components\DateTimePicker::make('expires_at')
                                    ->label('Son Geçerlilik Tarihi')
                                    ->minDate(now())
                                    ->default(now()->addYear()),
                            ]),
                        
                        Forms\Components\Select::make('status')
                            ->label('Durum')
                            ->options([
                                'active' => 'Aktif',
                                'partially_used' => 'Kısmen Kullanılmış',
                                'fully_used' => 'Tamamen Kullanılmış',
                                'expired' => 'Süresi Dolmuş',
                                'cancelled' => 'İptal Edilmiş',
                            ])
                            ->default('active')
                            ->required()
                            ->disabled(fn (?Model $record) => !$record || $record->status === 'fully_used'),
                        
                        Forms\Components\Select::make('return_request_id')
                            ->label('İlişkili İade Talebi')
                            ->relationship('returnRequest', 'id')
                            ->getOptionLabelFromRecordUsing(fn (Model $record) => 
                                "#{$record->id} - {$record->customer_name} ({$record->store->name})"
                            )
                            ->searchable()
                            ->preload()
                            ->disabled(),
                    ]),
                
                Section::make('Notlar')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notlar')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kod')
                    ->searchable()
                    ->copyable()
                    ->weight(FontWeight::Bold),
                
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Mağaza')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Müşteri')
                    ->searchable()
                    ->description(fn (Model $record): string => $record->customer_email),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Tutar')
                    ->money(fn (Model $record) => strtolower($record->currency))
                    ->sortable()
                    ->alignment(Alignment::End),
                
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Kalan')
                    ->money(fn (Model $record) => strtolower($record->currency))
                    ->sortable()
                    ->alignment(Alignment::End)
                    ->color(fn (Model $record) => 
                        $record->remaining_amount == 0 ? 'danger' : 
                        ($record->remaining_amount < $record->amount ? 'warning' : 'success')
                    ),
                
                Tables\Columns\TextColumn::make('usage_percentage')
                    ->label('Kullanım')
                    ->getStateUsing(fn (Model $record) => 
                        $record->amount > 0 
                            ? round((($record->amount - $record->remaining_amount) / $record->amount) * 100) . '%'
                            : '0%'
                    )
                    ->badge()
                    ->color(fn (string $state): string => match(true) {
                        $state === '0%' => 'success',
                        $state === '100%' => 'danger',
                        default => 'warning',
                    }),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'partially_used',
                        'danger' => fn ($state) => in_array($state, ['fully_used', 'expired', 'cancelled']),
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'active' => 'Aktif',
                        'partially_used' => 'Kısmen Kullanılmış',
                        'fully_used' => 'Tamamen Kullanılmış',
                        'expired' => 'Süresi Dolmuş',
                        'cancelled' => 'İptal Edilmiş',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Son Geçerlilik')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (?string $state): string => 
                        $state && now()->gt($state) ? 'danger' : 'gray'
                    ),
                
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Son Kullanım')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('returnRequest.id')
                    ->label('İade Talebi')
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '-')
                    ->url(fn ($record) => 
                        $record->return_request_id 
                            ? route('filament.admin.resources.return-requests.view', $record->return_request_id)
                            : null
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('Mağaza')
                    ->relationship('store', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'active' => 'Aktif',
                        'partially_used' => 'Kısmen Kullanılmış',
                        'fully_used' => 'Tamamen Kullanılmış',
                        'expired' => 'Süresi Dolmuş',
                        'cancelled' => 'İptal Edilmiş',
                    ]),
                
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Yakında Süresi Dolacak')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('expires_at', '<=', now()->addDays(30))
                              ->where('expires_at', '>', now())
                              ->where('status', 'active')
                    ),
                
                Tables\Filters\Filter::make('has_balance')
                    ->label('Bakiyesi Olanlar')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('remaining_amount', '>', 0)
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Model $record) => !in_array($record->status, ['fully_used', 'cancelled'])),
                
                Tables\Actions\Action::make('use_credit')
                    ->label('Kullan')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->visible(fn (Model $record) => $record->canBeUsed())
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Kullanılacak Tutar')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(fn (Model $record) => $record->remaining_amount)
                            ->prefix(fn (Model $record) => $record->currency)
                            ->helperText(fn (Model $record) => 
                                "Maksimum kullanılabilir: {$record->remaining_amount} {$record->currency}"
                            ),
                        
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('İşlem Referansı')
                            ->required()
                            ->placeholder('Örn: Sipariş #12345'),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Notlar')
                            ->rows(2),
                    ])
                    ->action(function (Model $record, array $data): void {
                        $record->use(
                            $data['amount'], 
                            null,
                            $data['transaction_reference'] ?? null,
                            $data['notes'] ?? null
                        );
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Store credit kullanıldı')
                            ->body("{$data['amount']} {$record->currency} tutarında store credit kullanıldı.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Store Credit Kullan')
                    ->modalDescription('Bu işlem geri alınamaz. Devam etmek istediğinize emin misiniz?'),
                
                Tables\Actions\Action::make('cancel')
                    ->label('İptal Et')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Model $record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->modalHeading('Store Credit İptal Et')
                    ->modalDescription('Bu store credit iptal edilecek ve bir daha kullanılamayacak. Devam etmek istediğinize emin misiniz?')
                    ->action(function (Model $record): void {
                        $record->update(['status' => 'cancelled']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Store credit iptal edildi')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getWidgets(): array
    {
        return [
            StoreCreditResource\Widgets\StoreCreditStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStoreCredits::route('/'),
            'create' => Pages\CreateStoreCredit::route('/create'),
            'view' => Pages\ViewStoreCredit::route('/{record}'),
            'edit' => Pages\EditStoreCredit::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['store', 'returnRequest']);
    }
    
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Müşteri' => $record->customer_name,
            'Kalan' => "{$record->remaining_amount} {$record->currency}",
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'customer_name', 'customer_email'];
    }
}