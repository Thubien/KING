<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Models\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Rules\UkrainianIbanRule;
use App\Rules\UkrainianMfoRule;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Bank Accounts';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?string $modelLabel = 'Bank Account';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🏦 Bank Information')
                    ->description('Add any bank from anywhere in the world')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('country_code')
                                    ->label('🌍 Country')
                                    ->options([
                                        'US' => '🇺🇸 United States',
                                        'UK' => '🇬🇧 United Kingdom', 
                                        'CA' => '🇨🇦 Canada',
                                        'AU' => '🇦🇺 Australia',
                                        'DE' => '🇩🇪 Germany',
                                        'FR' => '🇫🇷 France',
                                        'TR' => '🇹🇷 Turkey',
                                        'UA' => '🇺🇦 Ukraine',
                                        'ES' => '🇪🇸 Spain',
                                        'IT' => '🇮🇹 Italy',
                                        'NL' => '🇳🇱 Netherlands',
                                        'BE' => '🇧🇪 Belgium',
                                        'CH' => '🇨🇭 Switzerland',
                                        'AT' => '🇦🇹 Austria',
                                        'SE' => '🇸🇪 Sweden',
                                        'NO' => '🇳🇴 Norway',
                                        'DK' => '🇩🇰 Denmark',
                                        'FI' => '🇫🇮 Finland',
                                        'IE' => '🇮🇪 Ireland',
                                        'PT' => '🇵🇹 Portugal',
                                        'OTHER' => '🌐 Other Country'
                                    ])
                                    ->searchable()
                                    ->default('US')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        // Clear previous suggestions when country changes
                                        $set('bank_name', null);
                                    }),

                                Forms\Components\Select::make('bank_type')
                                    ->label('🏛️ Institution Type')
                                    ->options(BankAccount::getSuggestedTypes())
                                    ->default('commercial')
                                    ->required(),
                            ]),

                        Forms\Components\TextInput::make('bank_name')
                            ->label('🏦 Bank Name')
                            ->placeholder('Enter your bank name...')
                            ->datalist(fn (Get $get) => BankAccount::getPopularBanksForCountry($get('country_code') ?? 'US'))
                            ->required()
                            ->live()
                            ->helperText('Start typing and we\'ll suggest popular banks in your country'),

                        Forms\Components\TextInput::make('bank_branch')
                            ->label('🏢 Branch Name/Location')
                            ->placeholder('Main Branch, Downtown, etc.')
                            ->helperText('Optional: Specify which branch or location'),
                    ]),

                Forms\Components\Section::make('💳 Account Details')
                    ->schema([
                        Forms\Components\TextInput::make('account_name')
                            ->label('📝 Account Name/Description')
                            ->placeholder('Primary Checking Account')
                            ->required()
                            ->helperText('How you want to identify this account'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('currency')
                                    ->label('💰 Currency')
                                    ->options([
                                        'USD' => '🇺🇸 USD - US Dollar',
                                        'EUR' => '🇪🇺 EUR - Euro',
                                        'GBP' => '🇬🇧 GBP - British Pound',
                                        'CAD' => '🇨🇦 CAD - Canadian Dollar',
                                        'AUD' => '🇦🇺 AUD - Australian Dollar',
                                        'TRY' => '🇹🇷 TRY - Turkish Lira',
                                        'UAH' => '🇺🇦 UAH - Ukrainian Hryvnia',
                                        'CHF' => '🇨🇭 CHF - Swiss Franc',
                                        'SEK' => '🇸🇪 SEK - Swedish Krona',
                                        'NOK' => '🇳🇴 NOK - Norwegian Krone',
                                        'DKK' => '🇩🇰 DKK - Danish Krone',
                                    ])
                                    ->searchable()
                                    ->default('USD')
                                    ->required(),

                                Forms\Components\TextInput::make('current_balance')
                                    ->label('💵 Current Balance')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0)
                                    ->prefix('$')
                                    ->helperText('Optional: Enter current balance'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_primary')
                                    ->label('⭐ Primary Account')
                                    ->helperText('Make this your main account'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('✅ Active')
                                    ->default(true)
                                    ->helperText('Account is currently in use'),
                            ]),
                    ]),

                Forms\Components\Section::make('🔢 Banking Details')
                    ->description('Enter the banking details specific to your country')
                    ->schema([
                        Forms\Components\Placeholder::make('banking_info')
                            ->label('')
                            ->content(fn (Get $get) => 
                                'Required fields for ' . ($get('country_code') ? strtoupper($get('country_code')) : 'US') . ' banks:'
                            ),

                        // Universal fields
                        Forms\Components\TextInput::make('account_number')
                            ->label('🔢 Account Number')
                            ->password()
                            ->revealable()
                            ->placeholder('Your account number (encrypted)')
                            ->helperText('Will be encrypted for security'),

                        // US specific
                        Forms\Components\TextInput::make('routing_number')
                            ->label('🏦 Routing Number')
                            ->placeholder('9-digit routing number')
                            ->maxLength(9)
                            ->visible(fn (Get $get) => $get('country_code') === 'US')
                            ->helperText('9-digit bank routing number'),

                        // UK specific  
                        Forms\Components\TextInput::make('sort_code')
                            ->label('🔢 Sort Code')
                            ->placeholder('XX-XX-XX')
                            ->maxLength(8)
                            ->visible(fn (Get $get) => $get('country_code') === 'UK')
                            ->helperText('6-digit sort code (XX-XX-XX format)'),

                        // Australia specific
                        Forms\Components\TextInput::make('bsb_number')
                            ->label('🏦 BSB Number')
                            ->placeholder('XXX-XXX')
                            ->maxLength(7)
                            ->visible(fn (Get $get) => $get('country_code') === 'AU')
                            ->helperText('6-digit BSB number'),

                        // Canada specific
                        Forms\Components\TextInput::make('institution_number')
                            ->label('🏛️ Institution Number')
                            ->placeholder('3-digit institution number')
                            ->maxLength(3)
                            ->visible(fn (Get $get) => $get('country_code') === 'CA')
                            ->helperText('3-digit institution number'),

                        // European countries (IBAN)
                        Forms\Components\TextInput::make('iban')
                            ->label('🌍 IBAN')
                            ->placeholder('Country-specific IBAN format')
                            ->visible(fn (Get $get) => in_array($get('country_code'), ['DE', 'FR', 'ES', 'IT', 'NL', 'BE', 'AT', 'CH', 'TR', 'UA']))
                            ->rules(fn (Get $get) => $get('country_code') === 'UA' ? [new UkrainianIbanRule()] : [])
                            ->helperText(fn (Get $get) => match($get('country_code')) {
                                'TR' => 'Turkish IBAN: TR + 24 digits',
                                'UA' => 'Ukrainian IBAN: UA + 27 digits (includes MFO code)',
                                'DE' => 'German IBAN: DE + 20 digits', 
                                'FR' => 'French IBAN: FR + 25 digits',
                                default => 'IBAN for your country'
                            }),

                        // Ukraine specific - MFO Code
                        Forms\Components\TextInput::make('bank_code')
                            ->label('🏦 MFO Code')
                            ->placeholder('305299')
                            ->maxLength(6)
                            ->visible(fn (Get $get) => $get('country_code') === 'UA')
                            ->rules([new UkrainianMfoRule()])
                            ->helperText('6-digit MFO (banking identifier) code - e.g., 305299 for PrivatBank')
                            ->suffixAction(fn ($state) => $state && UkrainianMfoRule::getBankName($state) 
                                ? \Filament\Forms\Components\Actions\Action::make('bank_info')
                                    ->icon('heroicon-m-information-circle')
                                    ->tooltip('Bank: ' . UkrainianMfoRule::getBankName($state))
                                    ->color('success')
                                : null
                            ),

                        // International
                        Forms\Components\TextInput::make('swift_code')
                            ->label('🌐 SWIFT/BIC Code')
                            ->placeholder('8 or 11 character SWIFT code')
                            ->maxLength(11)
                            ->helperText('International bank identifier (8 or 11 characters)'),

                        Forms\Components\TextInput::make('bic_code')
                            ->label('🏦 BIC Code')
                            ->placeholder('Bank Identifier Code')
                            ->visible(fn (Get $get) => in_array($get('country_code'), ['DE', 'FR', 'ES', 'IT', 'NL', 'BE', 'AT']))
                            ->helperText('Bank Identifier Code (if different from SWIFT)'),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('📝 Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('bank_address')
                            ->label('🏢 Bank Address')
                            ->placeholder('Bank headquarters or branch address...')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('bank_phone')
                                    ->label('📞 Bank Phone')
                                    ->placeholder('+1 (555) 123-4567')
                                    ->tel(),

                                Forms\Components\TextInput::make('bank_website')
                                    ->label('🌐 Bank Website')
                                    ->placeholder('https://bankname.com')
                                    ->url(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bank_info')
                    ->label('🏦 Bank')
                    ->getStateUsing(fn (BankAccount $record): string => $record->getFullBankInfo())
                    ->description(fn (BankAccount $record) => $record->country_code 
                        ? '🌍 ' . strtoupper($record->country_code) 
                        : null)
                    ->searchable(['bank_name', 'bank_type'])
                    ->weight(FontWeight::SemiBold),
                    
                Tables\Columns\TextColumn::make('account_name')
                    ->label('📝 Account Name')
                    ->searchable()
                    ->description(fn (BankAccount $record) => $record->getMaskedAccountNumber()),
                    
                Tables\Columns\TextColumn::make('current_balance')
                    ->label('💰 Balance')
                    ->formatStateUsing(fn (BankAccount $record) => $record->getFormattedBalance())
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('⭐ Primary')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus'),

                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('🚦 Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country_code')
                    ->label('🌍 Country')
                    ->options([
                        'US' => '🇺🇸 United States',
                        'UK' => '🇬🇧 United Kingdom',
                        'CA' => '🇨🇦 Canada',
                        'AU' => '🇦🇺 Australia',
                        'DE' => '🇩🇪 Germany',
                        'FR' => '🇫🇷 France',
                        'TR' => '🇹🇷 Turkey',
                        'UA' => '🇺🇦 Ukraine',
                        'ES' => '🇪🇸 Spain',
                        'IT' => '🇮🇹 Italy',
                    ])
                    ->searchable(),

                Tables\Filters\SelectFilter::make('bank_type')
                    ->label('🏛️ Institution Type')
                    ->options(BankAccount::getSuggestedTypes()),
                    
                Tables\Filters\SelectFilter::make('currency')
                    ->label('💰 Currency')
                    ->options([
                        'USD' => '🇺🇸 USD',
                        'EUR' => '🇪🇺 EUR',
                        'GBP' => '🇬🇧 GBP',
                        'CAD' => '🇨🇦 CAD',
                        'AUD' => '🇦🇺 AUD',
                        'TRY' => '🇹🇷 TRY',
                        'UAH' => '🇺🇦 UAH',
                        'CHF' => '🇨🇭 CHF',
                    ])
                    ->searchable(),
                    
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('⭐ Primary Account'),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('🚦 Active Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),

                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),

                Tables\Actions\Action::make('adjustBalance')
                    ->label('💰 Adjust Balance')
                    ->icon('heroicon-m-calculator')
                    ->color('warning')
                    ->form([
                        Forms\Components\Radio::make('adjustment_type')
                            ->label('Adjustment Type')
                            ->options([
                                'add' => '➕ Add to Balance',
                                'subtract' => '➖ Subtract from Balance',
                                'set' => '⚖️ Set Balance',
                            ])
                            ->default('add')
                            ->required(),
                            
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->prefix(fn ($record) => match($record->currency) {
                                'USD' => '$',
                                'EUR' => '€',
                                'GBP' => '£',
                                'UAH' => '₴',
                                'TRY' => '₺',
                                default => $record->currency . ' '
                            }),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Reason for balance adjustment')
                            ->required(),
                    ])
                    ->action(function (BankAccount $record, array $data): void {
                        switch ($data['adjustment_type']) {
                            case 'add':
                                $record->addBalance($data['amount'], $data['description']);
                                break;
                            case 'subtract':
                                $record->subtractBalance($data['amount'], $data['description']);
                                break;
                            case 'set':
                                $record->update([
                                    'current_balance' => $data['amount']
                                ]);
                                $record->logBalanceChange('set_to', $data['amount'], $data['description']);
                                break;
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Balance Updated')
                            ->body("Balance adjustment completed: {$data['description']}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_primary', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $company = auth()->user()?->company;
        if (!$company) return null;
        
        return static::getModel()::where('company_id', $company->id)
            ->where('is_active', true)
            ->count();
    }
}