<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $navigationGroup = 'Company Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Company Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Acme Corporation'),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Auto-generated from company name'),
                        Forms\Components\TextInput::make('domain')
                            ->maxLength(255)
                            ->url()
                            ->prefix('https://')
                            ->placeholder('example.com'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('logo_url')
                            ->url()
                            ->maxLength(255)
                            ->prefix('https://')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Select::make('timezone')
                            ->required()
                            ->options(timezone_identifiers_list())
                            ->searchable()
                            ->default('UTC'),
                        Forms\Components\Select::make('currency')
                            ->required()
                            ->options([
                                'USD' => 'USD - US Dollar',
                                'EUR' => 'EUR - Euro',
                                'GBP' => 'GBP - British Pound',
                                'CAD' => 'CAD - Canadian Dollar',
                                'AUD' => 'AUD - Australian Dollar',
                                'TRY' => 'TRY - Turkish Lira',
                                'UAH' => 'UAH - Ukrainian Hryvnia',
                            ])
                            ->default('USD'),
                        Forms\Components\KeyValue::make('settings')
                            ->label('Additional Settings')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Subscription & Billing')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('active'),
                        Forms\Components\Select::make('plan')
                            ->required()
                            ->options([
                                'starter' => 'Starter (3 stores)',
                                'professional' => 'Professional (10 stores)',
                                'enterprise' => 'Enterprise (Unlimited)',
                            ])
                            ->default('starter'),
                        Forms\Components\DateTimePicker::make('plan_expires_at')
                            ->label('Plan Expires At'),
                        Forms\Components\Toggle::make('is_trial')
                            ->label('On Trial Period')
                            ->reactive(),
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->visible(fn ($get) => $get('is_trial'))
                            ->required(fn ($get) => $get('is_trial')),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Premium Features')
                    ->schema([
                        Forms\Components\Toggle::make('api_integrations_enabled')
                            ->label('API Integrations'),
                        Forms\Components\Toggle::make('webhooks_enabled')
                            ->label('Webhooks'),
                        Forms\Components\Toggle::make('real_time_sync_enabled')
                            ->label('Real-time Sync'),
                        Forms\Components\TextInput::make('max_api_calls_per_month')
                            ->numeric()
                            ->label('API Call Limit/Month')
                            ->default(1000),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('domain')
                    ->searchable()
                    ->icon('heroicon-m-globe-alt')
                    ->url(fn ($record) => $record->domain ? 'https://' . $record->domain : null)
                    ->openUrlInNewTab(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\BadgeColumn::make('plan')
                    ->colors([
                        'gray' => 'starter',
                        'primary' => 'professional',
                        'success' => 'enterprise',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('stores_count')
                    ->counts('stores')
                    ->label('Stores')
                    ->badge(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->badge(),
                Tables\Columns\TextColumn::make('currency')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_trial')
                    ->boolean()
                    ->label('Trial')
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-check-circle'),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record?->isTrialExpired() ? 'danger' : 'warning')
                    ->visible(fn ($record) => $record?->is_trial),
                Tables\Columns\IconColumn::make('api_integrations_enabled')
                    ->boolean()
                    ->label('API')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('plan')
                    ->options([
                        'starter' => 'Starter',
                        'professional' => 'Professional',
                        'enterprise' => 'Enterprise',
                    ]),
                Tables\Filters\TernaryFilter::make('is_trial')
                    ->label('Trial Status'),
                Tables\Filters\TernaryFilter::make('api_integrations_enabled')
                    ->label('API Enabled'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('super_admin')),
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'view' => Pages\ViewCompany::route('/{record}'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
    
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }
}
