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

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Bank Accounts';
    protected static ?string $modelLabel = 'Bank Account';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bank_type')
                    ->label('Bank Type')
                    ->options([
                        BankAccount::TYPE_MERCURY => 'Mercury Bank',
                        BankAccount::TYPE_PAYONEER => 'Payoneer',
                        BankAccount::TYPE_CHASE => 'Chase Bank',
                        BankAccount::TYPE_WELLS_FARGO => 'Wells Fargo',
                        BankAccount::TYPE_BANK_OF_AMERICA => 'Bank of America',
                        BankAccount::TYPE_OTHER => 'Other Bank',
                    ])
                    ->required(),
                    
                Forms\Components\TextInput::make('account_name')
                    ->label('Account Name')
                    ->placeholder('Primary Checking Account')
                    ->required(),
                    
                Forms\Components\TextInput::make('account_number')
                    ->label('Account Number')
                    ->password()
                    ->revealable()
                    ->placeholder('Will be encrypted'),
                    
                Forms\Components\TextInput::make('routing_number')
                    ->label('Routing Number')
                    ->password()
                    ->revealable()
                    ->placeholder('Will be encrypted'),
                    
                Forms\Components\TextInput::make('iban')
                    ->label('IBAN')
                    ->placeholder('For international accounts'),
                    
                Forms\Components\TextInput::make('swift_code')
                    ->label('SWIFT Code')
                    ->placeholder('For international accounts'),
                    
                Forms\Components\Select::make('currency')
                    ->options([
                        'USD' => 'USD - US Dollar',
                        'EUR' => 'EUR - Euro',
                        'GBP' => 'GBP - British Pound',
                    ])
                    ->default('USD')
                    ->required(),
                    
                Forms\Components\TextInput::make('current_balance')
                    ->label('Current Balance')
                    ->numeric()
                    ->step(0.01)
                    ->default(0)
                    ->prefix('$'),
                    
                Forms\Components\Toggle::make('is_primary')
                    ->label('Primary Account')
                    ->helperText('Only one primary account allowed per company'),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                    
                Forms\Components\KeyValue::make('metadata')
                    ->label('Additional Information')
                    ->keyLabel('Key')
                    ->valueLabel('Value'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bank_type')
                    ->label('Bank')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        BankAccount::TYPE_MERCURY => 'primary',
                        BankAccount::TYPE_PAYONEER => 'warning',
                        BankAccount::TYPE_CHASE => 'info',
                        BankAccount::TYPE_WELLS_FARGO => 'success',
                        BankAccount::TYPE_BANK_OF_AMERICA => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (BankAccount $record): string => $record->getBankTypeName()),
                    
                Tables\Columns\TextColumn::make('account_name')
                    ->label('Account Name')
                    ->weight(FontWeight::SemiBold),
                    
                Tables\Columns\TextColumn::make('masked_account')
                    ->label('Account Number')
                    ->getStateUsing(fn (BankAccount $record): string => $record->getMaskedAccountNumber()),
                    
                Tables\Columns\TextColumn::make('currency')
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Balance')
                    ->money(fn (BankAccount $record): string => $record->currency)
                    ->weight(FontWeight::Bold)
                    ->color(fn ($state): string => $state >= 0 ? 'success' : 'danger'),
                    
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('last_sync_at')
                    ->label('Last Sync')
                    ->dateTime()
                    ->since()
                    ->placeholder('Never'),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bank_type')
                    ->label('Bank Type')
                    ->options([
                        BankAccount::TYPE_MERCURY => 'Mercury Bank',
                        BankAccount::TYPE_PAYONEER => 'Payoneer',
                        BankAccount::TYPE_CHASE => 'Chase Bank',
                        BankAccount::TYPE_WELLS_FARGO => 'Wells Fargo',
                        BankAccount::TYPE_BANK_OF_AMERICA => 'Bank of America',
                        BankAccount::TYPE_OTHER => 'Other Bank',
                    ]),
                    
                Tables\Filters\SelectFilter::make('currency')
                    ->options([
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'GBP' => 'GBP',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary Account'),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\Action::make('adjustBalance')
                    ->label('Adjust Balance')
                    ->icon('heroicon-m-calculator')
                    ->color('info')
                    ->form([
                        Forms\Components\Radio::make('adjustment_type')
                            ->label('Adjustment Type')
                            ->options([
                                'add' => 'Add to Balance',
                                'subtract' => 'Subtract from Balance',
                                'set' => 'Set Balance',
                            ])
                            ->default('add')
                            ->required(),
                            
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->prefix('$'),
                            
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
                    }),
                    
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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