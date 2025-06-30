<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentProcessorAccountResource\Pages;
use App\Models\PaymentProcessorAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentProcessorAccountResource extends Resource
{
    protected static ?string $model = PaymentProcessorAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Payment Processors';

    protected static ?string $modelLabel = 'Payment Processor';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('processor_type')
                    ->label('Processor Type')
                    ->options([
                        PaymentProcessorAccount::TYPE_STRIPE => 'Stripe',
                        PaymentProcessorAccount::TYPE_PAYPAL => 'PayPal',
                        PaymentProcessorAccount::TYPE_SHOPIFY_PAYMENTS => 'Shopify Payments',
                        PaymentProcessorAccount::TYPE_MANUAL => 'Manual Entry',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('account_identifier')
                    ->label('Account ID/Email')
                    ->placeholder('account_id or email@example.com'),

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

                Forms\Components\TextInput::make('pending_balance')
                    ->label('Pending Balance')
                    ->numeric()
                    ->step(0.01)
                    ->default(0)
                    ->prefix('$'),

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
                Tables\Columns\TextColumn::make('processor_type')
                    ->label('Processor')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PaymentProcessorAccount::TYPE_STRIPE => 'info',
                        PaymentProcessorAccount::TYPE_PAYPAL => 'warning',
                        PaymentProcessorAccount::TYPE_SHOPIFY_PAYMENTS => 'success',
                        PaymentProcessorAccount::TYPE_MANUAL => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (PaymentProcessorAccount $record): string => $record->getDisplayName()),

                Tables\Columns\TextColumn::make('currency')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Available')
                    ->money(fn (PaymentProcessorAccount $record): string => $record->currency)
                    ->weight(FontWeight::Bold)
                    ->color('success'),

                Tables\Columns\TextColumn::make('pending_balance')
                    ->label('Pending')
                    ->money(fn (PaymentProcessorAccount $record): string => $record->currency)
                    ->weight(FontWeight::Bold)
                    ->color('warning'),

                Tables\Columns\TextColumn::make('total_balance')
                    ->label('Total')
                    ->getStateUsing(fn (PaymentProcessorAccount $record): float => $record->getTotalBalance())
                    ->money(fn (PaymentProcessorAccount $record): string => $record->currency)
                    ->weight(FontWeight::Bold)
                    ->color('primary'),

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
                Tables\Filters\SelectFilter::make('processor_type')
                    ->label('Processor Type')
                    ->options([
                        PaymentProcessorAccount::TYPE_STRIPE => 'Stripe',
                        PaymentProcessorAccount::TYPE_PAYPAL => 'PayPal',
                        PaymentProcessorAccount::TYPE_SHOPIFY_PAYMENTS => 'Shopify Payments',
                        PaymentProcessorAccount::TYPE_MANUAL => 'Manual Entry',
                    ]),

                Tables\Filters\SelectFilter::make('currency')
                    ->options([
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'GBP' => 'GBP',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\Action::make('addBalance')
                    ->label('Add Balance')
                    ->icon('heroicon-m-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Radio::make('balance_type')
                            ->label('Balance Type')
                            ->options([
                                'current' => 'Add to Current Balance',
                                'pending' => 'Add to Pending Balance',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->prefix('$'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Reason for balance adjustment'),
                    ])
                    ->action(function (PaymentProcessorAccount $record, array $data): void {
                        if ($data['balance_type'] === 'current') {
                            $record->addCurrentBalance($data['amount'], $data['description']);
                        } else {
                            $record->addPendingBalance($data['amount'], $data['description']);
                        }
                    }),

                Tables\Actions\Action::make('processPayout')
                    ->label('Process Payout')
                    ->icon('heroicon-m-arrow-right')
                    ->color('warning')
                    ->visible(fn (PaymentProcessorAccount $record): bool => $record->pending_balance > 0)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Payout Amount')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->prefix('$')
                            ->maxValue(fn (PaymentProcessorAccount $record): float => $record->pending_balance),

                        Forms\Components\Textarea::make('description')
                            ->label('Payout Description')
                            ->placeholder('Manual payout processing'),
                    ])
                    ->action(function (PaymentProcessorAccount $record, array $data): void {
                        $record->movePendingToCurrent($data['amount'], $data['description']);
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('processor_type')
            ->poll('30s'); // Auto refresh every 30 seconds
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentProcessorAccounts::route('/'),
            'create' => Pages\CreatePaymentProcessorAccount::route('/create'),
            'edit' => Pages\EditPaymentProcessorAccount::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $company = auth()->user()?->company;
        if (! $company) {
            return null;
        }

        return static::getModel()::where('company_id', $company->id)
            ->where('is_active', true)
            ->count();
    }
}
