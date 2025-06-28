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
                                Forms\Components\TextInput::make('currency')
                                    ->required()
                                    ->maxLength(3)
                                    ->default('USD'),
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
