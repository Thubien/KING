<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnRequestResource\Pages;
use App\Filament\Resources\ReturnRequestResource\RelationManagers;
use App\Models\ReturnRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use App\Services\ReturnChecklistService;

class ReturnRequestResource extends Resource
{
    protected static ?string $model = ReturnRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationLabel = 'İade Yönetimi';
    protected static ?string $pluralLabel = 'İade Talepleri';
    protected static ?string $label = 'İade Talebi';
    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('İade Bilgileri')
                    ->description('İade talebi detayları')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Select::make('store_id')
                                    ->relationship('store', 'name')
                                    ->required()
                                    ->label('Mağaza')
                                    ->preload()
                                    ->disabled(fn ($context) => $context === 'edit'),
                                    
                                TextInput::make('order_number')
                                    ->required()
                                    ->label('Sipariş No')
                                    ->placeholder('SHP-2024-001')
                                    ->disabled(fn ($context) => $context === 'edit'),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Müşteri Bilgileri')
                    ->description('Müşteri iletişim bilgileri')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('customer_name')
                                    ->required()
                                    ->label('Müşteri Adı')
                                    ->disabled(fn ($context) => $context === 'edit'),
                                    
                                TextInput::make('customer_phone')
                                    ->tel()
                                    ->label('Müşteri Telefon')
                                    ->disabled(fn ($context) => $context === 'edit'),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Ürün ve İade Detayları')
                    ->description('İade edilen ürün bilgileri')
                    ->icon('heroicon-o-shopping-bag')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                TextInput::make('product_name')
                                    ->required()
                                    ->label('Ürün Adı')
                                    ->columnSpan(2)
                                    ->disabled(fn ($context) => $context === 'edit'),
                                    
                                TextInput::make('product_sku')
                                    ->label('SKU')
                                    ->placeholder('SKU-12345')
                                    ->disabled(fn ($context) => $context === 'edit'),
                            ]),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                TextInput::make('quantity')
                                    ->label('Adet')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->disabled(fn ($context) => $context === 'edit'),
                                    
                                TextInput::make('refund_amount')
                                    ->label('İade Tutarı')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step('0.01')
                                    ->placeholder('0.00')
                                    ->disabled(fn ($context) => $context === 'edit'),
                                    
                                Select::make('currency')
                                    ->label('Para Birimi')
                                    ->options([
                                        'USD' => 'USD',
                                        'EUR' => 'EUR',
                                        'TRY' => 'TRY',
                                    ])
                                    ->default('USD')
                                    ->disabled(fn ($context) => $context === 'edit'),
                            ]),
                            
                        Textarea::make('return_reason')
                            ->required()
                            ->label('İade Nedeni')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled(fn ($context) => $context === 'edit'),
                    ]),
                    
                Select::make('status')
                    ->options(ReturnRequest::STATUSES)
                    ->default('pending')
                    ->label('Durum')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, $record) {
                        if ($record && $state) {
                            \App\Services\ReturnChecklistService::createChecklistsForStage($record, $state);
                        }
                    })
                    ->visible(fn ($context) => $context === 'edit'),
                    
                Select::make('resolution')
                    ->options(ReturnRequest::RESOLUTIONS)
                    ->label('Çözüm')
                    ->visible(fn ($context, $get) => $context === 'edit' && in_array($get('status'), ['processing', 'completed'])),
                    
                TextInput::make('tracking_number')
                    ->label('Bizim Kargo Takip No')
                    ->placeholder('TR123456789')
                    ->helperText('Müşteriye gönderdiğimiz kargo')
                    ->visible(fn ($context, $get) => $context === 'edit' && in_array($get('status'), ['in_transit', 'processing', 'completed'])),
                    
                TextInput::make('customer_tracking_number')
                    ->label('Müşteri Kargo Takip No')
                    ->placeholder('MU123456789')
                    ->helperText('Müşterinin bize gönderdiği kargo')
                    ->visible(fn ($context, $get) => $context === 'edit' && in_array($get('status'), ['in_transit', 'processing', 'completed'])),
                    
                Textarea::make('notes')
                    ->label('Notlar')
                    ->rows(3)
                    ->columnSpanFull()
                    ->placeholder('İade ile ilgili özel notlar...')
                    ->visible(fn ($context) => $context === 'edit'),
                    
                Forms\Components\Section::make('Medya')
                    ->description('İade ile ilgili fotoğraf ve belgeler')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Forms\Components\FileUpload::make('media')
                            ->label('Fotoğraflar / Belgeler')
                            ->multiple()
                            ->image()
                            ->maxFiles(10)
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->directory('return-requests')
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull()
                            ->helperText('Maksimum 10 dosya yükleyebilirsiniz. Desteklenen formatlar: JPG, PNG, PDF'),
                    ])
                    ->visible(fn ($context) => $context === 'edit')
                    ->collapsible(),
                    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Sipariş No')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('customer_name')
                    ->label('Müşteri')
                    ->searchable(),
                    
                TextColumn::make('product_name')
                    ->label('Ürün')
                    ->limit(30)
                    ->searchable(),
                    
                TextColumn::make('store.name')
                    ->label('Mağaza')
                    ->sortable(),
                    
                BadgeColumn::make('status')
                    ->label('Durum')
                    ->formatStateUsing(fn ($state) => ReturnRequest::STATUSES[$state] ?? $state)
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'in_transit',
                        'info' => 'processing',
                        'success' => 'completed',
                    ]),
                    
                BadgeColumn::make('resolution')
                    ->label('Çözüm')
                    ->formatStateUsing(fn ($state) => $state ? ReturnRequest::RESOLUTIONS[$state] : '-')
                    ->colors([
                        'success' => 'refund',
                        'info' => 'exchange',
                        'warning' => 'store_credit',
                        'danger' => 'rejected',
                    ]),
                    
                TextColumn::make('created_at')
                    ->label('Talep Tarihi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(ReturnRequest::STATUSES),
                    
                Tables\Filters\SelectFilter::make('resolution')
                    ->label('Çözüm')
                    ->options(ReturnRequest::RESOLUTIONS),
                    
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('Mağaza')
                    ->relationship('store', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('view_kanban')
                    ->label('Kanban')
                    ->icon('heroicon-o-view-columns')
                    ->url(fn () => route('filament.admin.pages.return-kanban'))
                    ->color('info'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ChecklistsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnRequests::route('/'),
            'create' => Pages\CreateReturnRequest::route('/create'),
            'edit' => Pages\EditReturnRequest::route('/{record}/edit'),
        ];
    }
}
