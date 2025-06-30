<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Models\Store;
use App\Traits\HasSimpleAuthorization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class CustomerResource extends Resource
{
    use HasSimpleAuthorization;
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Customers';
    
    protected static ?string $modelLabel = 'Customer';
    
    protected static ?string $pluralModelLabel = 'Customers';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationGroup = 'Customer Relations';

    protected static function getResourcePermissions(): array
    {
        return [
            'viewAny' => ['owner', 'partner', 'staff', 'super_admin'],
            'view' => ['owner', 'partner', 'staff', 'super_admin'],
            'create' => ['owner', 'partner', 'staff', 'super_admin'],
            'update' => ['owner', 'partner', 'staff', 'super_admin'],
            'delete' => ['owner', 'staff', 'super_admin'],
            'deleteAny' => ['owner', 'super_admin'],
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Müşteri Bilgileri')
                    ->description('Temel müşteri bilgileri')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('store_id')
                                    ->label('Mağaza')
                                    ->relationship('store', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $store = Store::find($state);
                                            $set('company_id', $store?->company_id);
                                        }
                                    })
                                    ->disabled(fn (?Model $record) => $record !== null),
                                
                                Forms\Components\TextInput::make('name')
                                    ->label('Ad Soyad')
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\Select::make('status')
                                    ->label('Durum')
                                    ->options([
                                        'active' => 'Aktif',
                                        'inactive' => 'İnaktif',
                                        'blacklist' => 'Kara Liste',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->native(false),
                            ]),
                        
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('E-posta')
                                    ->email()
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('phone')
                                    ->label('Telefon')
                                    ->tel()
                                    ->placeholder('05XX XXX XX XX')
                                    ->maxLength(20),
                                
                                Forms\Components\TextInput::make('whatsapp_number')
                                    ->label('WhatsApp')
                                    ->tel()
                                    ->placeholder('90 5XX XXX XX XX')
                                    ->maxLength(20),
                            ]),
                        
                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('gender')
                                    ->label('Cinsiyet')
                                    ->options([
                                        'male' => 'Erkek',
                                        'female' => 'Kadın',
                                        'other' => 'Diğer',
                                    ])
                                    ->native(false),
                                
                                Forms\Components\DatePicker::make('birth_date')
                                    ->label('Doğum Tarihi')
                                    ->maxDate(now()->subYears(16)),
                                
                                Forms\Components\Select::make('preferred_contact_method')
                                    ->label('Tercih Edilen İletişim')
                                    ->options([
                                        'phone' => 'Telefon',
                                        'whatsapp' => 'WhatsApp',
                                        'email' => 'E-posta',
                                        'sms' => 'SMS',
                                    ])
                                    ->native(false),
                            ]),
                            
                        Forms\Components\Hidden::make('company_id')
                            ->default(fn () => auth()->user()->company_id),
                    ]),
                
                Section::make('Müşteri Segmentasyonu')
                    ->description('Etiketler ve notlar')
                    ->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->label('Etiketler')
                            ->suggestions([
                                'vip' => 'VIP',
                                'wholesale' => 'Toptan',
                                'problematic' => 'Sorunlu',
                                'returning' => 'Sürekli İade',
                                'new' => 'Yeni',
                                'loyal' => 'Sadık',
                            ])
                            ->separator(',')
                            ->splitKeys(['Tab', ','])
                            ->reorderable()
                            ->helperText('Enter veya virgül ile ayırın'),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Notlar')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->columns(1),
                
                Section::make('Kurumsal Bilgiler')
                    ->description('B2B müşteriler için')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Firma Adı')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('tax_number')
                            ->label('Vergi Numarası')
                            ->maxLength(20),
                    ])
                    ->columns(2)
                    ->collapsed(),
                
                Section::make('Pazarlama Tercihleri')
                    ->schema([
                        Forms\Components\Toggle::make('accepts_marketing')
                            ->label('Pazarlama iletişimlerini kabul ediyor')
                            ->default(true),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Müşteri')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (Model $record): ?string => 
                        $record->email ?: $record->phone
                    ),
                
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Mağaza')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('segment')
                    ->label('Segment')
                    ->getStateUsing(fn (Model $record): string => $record->getSegment())
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'VIP' => 'warning',
                        'Sadık Müşteri' => 'success',
                        'Risk Altında' => 'danger',
                        'Kayıp Müşteri' => 'gray',
                        'Yeni Müşteri' => 'info',
                        default => 'primary',
                    }),
                
                Tables\Columns\TagsColumn::make('tags')
                    ->label('Etiketler')
                    ->separator(','),
                
                Tables\Columns\TextColumn::make('total_orders')
                    ->label('Sipariş')
                    ->numeric()
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->formatStateUsing(fn ($state) => $state ?: '0'),
                
                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Toplam Harcama')
                    ->money(fn (Model $record) => strtolower($record->store->currency ?? 'try'))
                    ->sortable()
                    ->alignment(Alignment::End)
                    ->weight(FontWeight::Bold),
                
                Tables\Columns\TextColumn::make('avg_order_value')
                    ->label('Ort. Sepet')
                    ->money(fn (Model $record) => strtolower($record->store->currency ?? 'try'))
                    ->sortable()
                    ->alignment(Alignment::End)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('return_rate')
                    ->label('İade Oranı')
                    ->getStateUsing(fn (Model $record): string => $record->getReturnRate() . '%')
                    ->badge()
                    ->color(fn (Model $record): string => match(true) {
                        $record->getReturnRate() > 30 => 'danger',
                        $record->getReturnRate() > 15 => 'warning',
                        default => 'success',
                    })
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('last_order_date')
                    ->label('Son Sipariş')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (Model $record): ?string => 
                        $record->last_order_date 
                            ? $record->getDaysSinceLastOrder() . ' gün önce'
                            : null
                    )
                    ->color(fn (Model $record): string => match(true) {
                        $record->getDaysSinceLastOrder() > 90 => 'danger',
                        $record->getDaysSinceLastOrder() > 30 => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger' => 'blacklist',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'active' => 'Aktif',
                        'inactive' => 'İnaktif',
                        'blacklist' => 'Kara Liste',
                        default => $state,
                    }),
                    
                Tables\Columns\IconColumn::make('accepts_marketing')
                    ->label('Pazarlama')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('source')
                    ->label('Kaynak')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'manual' => 'Manuel',
                        'shopify' => 'Shopify',
                        'return' => 'İade',
                        'import' => 'İçe Aktarım',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
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
                        'inactive' => 'İnaktif',
                        'blacklist' => 'Kara Liste',
                    ]),
                
                Tables\Filters\Filter::make('tags')
                    ->form([
                        Forms\Components\Select::make('tag')
                            ->label('Etiket')
                            ->options([
                                'vip' => 'VIP',
                                'wholesale' => 'Toptan',
                                'problematic' => 'Sorunlu',
                                'returning' => 'Sürekli İade',
                                'new' => 'Yeni',
                                'loyal' => 'Sadık',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['tag'],
                            fn (Builder $query, $tag): Builder => $query->whereJsonContains('tags', $tag)
                        );
                    }),
                
                Tables\Filters\Filter::make('segment')
                    ->form([
                        Forms\Components\Select::make('segment')
                            ->label('Segment')
                            ->options([
                                'vip' => 'VIP Müşteriler',
                                'loyal' => 'Sadık Müşteriler (5+ sipariş)',
                                'at_risk' => 'Risk Altında (90+ gün)',
                                'lost' => 'Kayıp Müşteriler (180+ gün)',
                                'new' => 'Yeni Müşteriler (1 sipariş)',
                                'potential' => 'Potansiyel (0 sipariş)',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['segment'], function ($query, $segment) {
                            return match($segment) {
                                'vip' => $query->whereJsonContains('tags', 'vip'),
                                'loyal' => $query->where('total_orders', '>', 5),
                                'at_risk' => $query->whereBetween('last_order_date', [now()->subDays(180), now()->subDays(90)]),
                                'lost' => $query->where('last_order_date', '<', now()->subDays(180)),
                                'new' => $query->where('total_orders', 1),
                                'potential' => $query->where('total_orders', 0),
                                default => $query,
                            };
                        });
                    }),
                
                Tables\Filters\Filter::make('high_value')
                    ->label('Yüksek Değerli')
                    ->query(fn (Builder $query): Builder => $query->where('total_spent', '>=', 10000)),
                
                Tables\Filters\Filter::make('accepts_marketing')
                    ->label('Pazarlamayı Kabul Edenler')
                    ->query(fn (Builder $query): Builder => $query->where('accepts_marketing', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('add_tag')
                    ->label('Etiket Ekle')
                    ->icon('heroicon-m-tag')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('tag')
                            ->label('Etiket')
                            ->options([
                                'vip' => 'VIP',
                                'wholesale' => 'Toptan',
                                'problematic' => 'Sorunlu',
                                'returning' => 'Sürekli İade',
                                'loyal' => 'Sadık',
                            ])
                            ->required(),
                    ])
                    ->action(function (Model $record, array $data): void {
                        $record->addTag($data['tag']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Etiket eklendi')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('view_timeline')
                    ->label('Zaman Çizelgesi')
                    ->icon('heroicon-m-clock')
                    ->color('info')
                    ->url(fn (Model $record): string => 
                        static::getUrl('view', ['record' => $record, 'tab' => 'timeline'])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('add_tag_bulk')
                        ->label('Toplu Etiket Ekle')
                        ->icon('heroicon-m-tag')
                        ->form([
                            Forms\Components\Select::make('tag')
                                ->label('Etiket')
                                ->options([
                                    'vip' => 'VIP',
                                    'wholesale' => 'Toptan',
                                    'problematic' => 'Sorunlu',
                                    'returning' => 'Sürekli İade',
                                    'loyal' => 'Sadık',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            foreach ($records as $record) {
                                $record->addTag($data['tag']);
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Etiketler eklendi')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_order_date', 'desc')
            ->recordClasses(fn (Model $record) => match($record->status) {
                'blacklist' => 'opacity-75',
                default => null,
            });
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AddressesRelationManager::class,
            RelationManagers\TransactionsRelationManager::class,
            RelationManagers\ReturnsRelationManager::class,
            RelationManagers\StoreCreditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['store', 'transactions', 'returnRequests']);
    }
    
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Mağaza' => $record->store->name,
            'Telefon' => $record->phone,
            'Email' => $record->email,
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'company_name'];
    }
}