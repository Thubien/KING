<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Filament\Resources\StoreResource\RelationManagers;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    
    protected static ?string $navigationGroup = 'Business Management';
    
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', Store::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', Store::class) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isCompanyOwner() || auth()->user()?->isAdmin() || auth()->user()?->isPartner();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Company owners and admins see all stores in their company
        if ($user->isCompanyOwner() || $user->isAdmin()) {
            return $query->where('company_id', $user->company_id);
        }

        // Partners only see stores they have partnerships in
        if ($user->isPartner()) {
            $accessibleStoreIds = $user->getAccessibleStoreIds();
            return $query->whereIn('id', $accessibleStoreIds);
        }

        return $query->whereNull('id'); // Return empty for other user types
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Store Configuration')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->schema([
                                // Currency Warning Alert
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('currency_notice')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div class="bg-warning-50 dark:bg-warning-900/10 border border-warning-300 dark:border-warning-600 rounded-lg p-4">
                                                    <div class="flex">
                                                        <svg class="w-5 h-5 text-warning-600 dark:text-warning-400 mr-2 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                        </svg>
                                                        <div class="text-sm">
                                                            <p class="font-medium text-warning-800 dark:text-warning-200">Important: Currency Selection is Permanent</p>
                                                            <p class="mt-1 text-warning-700 dark:text-warning-300">Once you save this store, the primary currency cannot be changed. All financial data, transactions, and reports will be permanently recorded in the selected currency.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            '))
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => $record === null), // Only show on create
                                    
                                Forms\Components\Select::make('company_id')
                                    ->relationship('company', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('shopify_domain')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('shopify_store_id')
                                    ->maxLength(255),
                                Forms\Components\Select::make('currency')
                                    ->label('Primary Currency')
                                    ->required()
                                    ->searchable()
                                    ->options(function () {
                                        // Common currencies first
                                        $commonCurrencies = [
                                            'USD' => 'USD - US Dollar',
                                            'EUR' => 'EUR - Euro',
                                            'GBP' => 'GBP - British Pound',
                                            'TRY' => 'TRY - Turkish Lira',
                                            'KRW' => 'KRW - South Korean Won',
                                            'JPY' => 'JPY - Japanese Yen',
                                            'CNY' => 'CNY - Chinese Yuan',
                                            'RUB' => 'RUB - Russian Ruble',
                                            'INR' => 'INR - Indian Rupee',
                                            'BRL' => 'BRL - Brazilian Real',
                                            'CAD' => 'CAD - Canadian Dollar',
                                            'AUD' => 'AUD - Australian Dollar',
                                            'THB' => 'THB - Thai Baht',
                                            'SGD' => 'SGD - Singapore Dollar',
                                            'MXN' => 'MXN - Mexican Peso',
                                        ];
                                        
                                        // Add separator
                                        $allCurrencies = $commonCurrencies + ['---' => '─────────────────'];
                                        
                                        // Add all other ISO currencies
                                        $isoCurrencies = [
                                            'AED' => 'AED - UAE Dirham',
                                            'AFN' => 'AFN - Afghan Afghani',
                                            'ARS' => 'ARS - Argentine Peso',
                                            'BGN' => 'BGN - Bulgarian Lev',
                                            'CHF' => 'CHF - Swiss Franc',
                                            'CLP' => 'CLP - Chilean Peso',
                                            'COP' => 'COP - Colombian Peso',
                                            'CZK' => 'CZK - Czech Koruna',
                                            'DKK' => 'DKK - Danish Krone',
                                            'EGP' => 'EGP - Egyptian Pound',
                                            'HKD' => 'HKD - Hong Kong Dollar',
                                            'HUF' => 'HUF - Hungarian Forint',
                                            'IDR' => 'IDR - Indonesian Rupiah',
                                            'ILS' => 'ILS - Israeli New Shekel',
                                            'MAD' => 'MAD - Moroccan Dirham',
                                            'MYR' => 'MYR - Malaysian Ringgit',
                                            'NGN' => 'NGN - Nigerian Naira',
                                            'NOK' => 'NOK - Norwegian Krone',
                                            'NZD' => 'NZD - New Zealand Dollar',
                                            'PEN' => 'PEN - Peruvian Sol',
                                            'PHP' => 'PHP - Philippine Peso',
                                            'PKR' => 'PKR - Pakistani Rupee',
                                            'PLN' => 'PLN - Polish Zloty',
                                            'RON' => 'RON - Romanian Leu',
                                            'SAR' => 'SAR - Saudi Riyal',
                                            'SEK' => 'SEK - Swedish Krona',
                                            'TWD' => 'TWD - Taiwan Dollar',
                                            'UAH' => 'UAH - Ukrainian Hryvnia',
                                            'VND' => 'VND - Vietnamese Dong',
                                            'ZAR' => 'ZAR - South African Rand',
                                        ];
                                        
                                        return $allCurrencies + $isoCurrencies;
                                    })
                                    ->default('USD')
                                    ->helperText('Select the main currency for this store')
                                    ->hintIcon('heroicon-o-exclamation-triangle')
                                    ->hintColor('warning')
                                    ->hint('Cannot be changed later')
                                    ->disabled(fn ($record) => $record !== null) // Disable on edit
                                    ->dehydrated(fn ($record) => $record === null) // Only save on create
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $livewire) {
                                        if ($state && !session('currency_warning_shown_' . $livewire->getId())) {
                                            \Filament\Notifications\Notification::make()
                                                ->warning()
                                                ->persistent()
                                                ->title('Important: Currency Selection is Permanent')
                                                ->body('Once saved, the primary currency cannot be changed. All transactions, reports, and balances will be permanently recorded in ' . $state . '.')
                                                ->actions([
                                                    \Filament\Notifications\Actions\Action::make('understand')
                                                        ->label('I Understand')
                                                        ->close()
                                                ])
                                                ->send();
                                            
                                            session(['currency_warning_shown_' . $livewire->getId() => true]);
                                        }
                                    }),
                                Forms\Components\TextInput::make('country_code')
                                    ->maxLength(2),
                                Forms\Components\TextInput::make('timezone')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('logo_url')
                                    ->maxLength(255),
                            ]),
                        
                        Forms\Components\Tabs\Tab::make('API Integrations')
                            ->schema([
                                Forms\Components\Placeholder::make('api_integrations_notice')
                                    ->label('')
                                    ->content(function () {
                                        $user = auth()->user();
                                        if (!$user->company->canUseApiIntegrations()) {
                                            return '**API Integrations are Premium Features**

 Upgrade to Premium or Enterprise to unlock:
• Real-time Stripe transaction sync
• Automatic categorization
• Live webhooks
• PayPal API integration
• Advanced reconciliation

Currently on: **' . ucfirst($user->company->subscription_plan) . ' Plan**';
                                        }
                                        
                                        return '**API Integrations Enabled**

Your ' . ucfirst($user->company->subscription_plan) . ' plan includes:
• Real-time transaction sync
• Automatic categorization  
• Live webhooks
• Advanced reconciliation';
                                    })
                                    ->columnSpanFull(),
                                    
                                Forms\Components\Section::make('Stripe Integration')
                                    ->description('Connect your Stripe account for real-time transaction sync')
                                    ->schema([
                                        Forms\Components\TextInput::make('stripe_secret_key')
                                            ->label('Stripe Secret Key')
                                            ->password()
                                            ->placeholder('sk_live_... or sk_test_...')
                                            ->helperText('Your Stripe secret API key for accessing transaction data')
                                            ->disabled(fn () => !auth()->user()?->company?->canUseApiIntegrations())
                                            ->dehydrated(fn ($state) => filled($state)),
                                            
                                        Forms\Components\TextInput::make('stripe_publishable_key')
                                            ->label('Stripe Publishable Key')
                                            ->placeholder('pk_live_... or pk_test_...')
                                            ->helperText('Optional: For enhanced webhook verification')
                                            ->disabled(fn () => !auth()->user()?->company?->canUseApiIntegrations())
                                            ->dehydrated(fn ($state) => filled($state)),
                                            
                                        Forms\Components\Toggle::make('stripe_sync_enabled')
                                            ->label('Enable Stripe Sync')
                                            ->helperText('Automatically sync transactions from Stripe')
                                            ->disabled(fn () => !auth()->user()?->company?->canUseApiIntegrations())
                                            ->default(false),
                                            
                                        Forms\Components\DateTimePicker::make('last_stripe_sync')
                                            ->label('Last Stripe Sync')
                                            ->displayFormat('M j, Y H:i')
                                            ->disabled()
                                            ->helperText('When transactions were last synced from Stripe'),
                                    ])
                                    ->collapsible()
                                    ->collapsed(fn () => !auth()->user()?->company?->canUseApiIntegrations()),
                                    
                                Forms\Components\Section::make('PayPal Integration')
                                    ->description('Connect PayPal for transaction sync (Coming Soon)')
                                    ->schema([
                                        Forms\Components\Placeholder::make('paypal_coming_soon')
                                            ->label('')
                                            ->content('**PayPal API Integration - Coming Soon**

We\'re working on PayPal API integration. For now, you can:
• Upload PayPal CSV exports manually
• Use our CSV import wizard
• Set up automatic categorization rules')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(true),
                            ])
                            ->badge(fn () => auth()->user()?->company?->canUseApiIntegrations() ? 'Enabled' : 'Premium'),
                            
                        Forms\Components\Tabs\Tab::make('Configuration')
                            ->schema([
                                Forms\Components\TextInput::make('shopify_webhook_endpoints'),
                                Forms\Components\TextInput::make('status')
                                    ->required(),
                                Forms\Components\DateTimePicker::make('last_sync_at'),
                                Forms\Components\TextInput::make('sync_errors'),
                                Forms\Components\TextInput::make('settings'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('shopify_domain')
                    ->searchable(),
                Tables\Columns\TextColumn::make('shopify_store_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('timezone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('logo_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('last_sync_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
