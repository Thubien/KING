<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettlementResource\Pages;
use App\Models\Partnership;
use App\Models\Settlement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SettlementResource extends Resource
{
    protected static ?string $model = Settlement::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Debt Settlements';

    protected static ?string $navigationGroup = 'Partnership';

    protected static ?string $modelLabel = 'Settlement';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Company owners and admins see all settlements in their company
        if ($user->isCompanyOwner() || $user->isAdmin()) {
            return $query->whereHas('partnership.store', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        // Partners see settlements for their partnerships
        if ($user->isPartner()) {
            return $query->whereHas('partnership', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query->whereNull('id'); // Return empty for other user types
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Settlement Details')
                    ->schema([
                        Forms\Components\Select::make('partnership_id')
                            ->label('Partnership')
                            ->relationship('partnership', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->store->name} - {$record->user->name} ({$record->ownership_percentage}%)"
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $partnership = Partnership::find($state);
                                    $set('debt_balance_before', $partnership->debt_balance);
                                    $set('currency', $partnership->store->currency);
                                    $set('_current_debt', $partnership->getFormattedDebtBalance());
                                }
                            })
                            ->helperText(fn ($get) => $get('_current_debt')
                                ? "Current debt balance: {$get('_current_debt')}"
                                : 'Select a partnership to see current debt balance'),

                        Forms\Components\Select::make('settlement_type')
                            ->label('Settlement Type')
                            ->options(Settlement::getTypeOptions())
                            ->required()
                            ->reactive()
                            ->helperText(function ($state) {
                                return match ($state) {
                                    'payment' => 'Partner pays back debt to reduce balance',
                                    'withdrawal' => 'Partner withdraws profit (increases debt)',
                                    'expense' => 'Settle personal expense as debt',
                                    'adjustment' => 'Manual adjustment to debt balance',
                                    'profit_share' => 'Distribute profit share to partner',
                                    default => 'Select settlement type'
                                };
                            }),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Amount')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->prefix(fn ($get) => $get('currency') ?? 'USD')
                                    ->helperText('Enter the settlement amount'),

                                Forms\Components\Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options([
                                        'bank_transfer' => 'Bank Transfer',
                                        'cash' => 'Cash',
                                        'check' => 'Check',
                                        'paypal' => 'PayPal',
                                        'other' => 'Other',
                                    ])
                                    ->visible(fn ($get) => in_array($get('settlement_type'), ['payment', 'withdrawal'])),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->required()
                            ->placeholder('Describe the purpose of this settlement...'),

                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->placeholder('Transaction ID, check number, etc.')
                            ->visible(fn ($get) => in_array($get('settlement_type'), ['payment', 'withdrawal'])),

                        Forms\Components\Hidden::make('currency'),
                        Forms\Components\Hidden::make('debt_balance_before'),
                        Forms\Components\Hidden::make('_current_debt'),
                        Forms\Components\Hidden::make('initiated_by_user_id')
                            ->default(Auth::id()),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Approval')
                    ->schema([
                        Forms\Components\Placeholder::make('approval_status')
                            ->label('Status')
                            ->content(fn ($record) => $record ? Settlement::getStatusOptions()[$record->status] : 'Pending'),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->visible(fn ($record) => $record && $record->status === 'rejected')
                            ->disabled(),
                    ])
                    ->visible(fn ($record) => $record !== null)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('partnership.store.name')
                    ->label('Store')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('partnership.user.name')
                    ->label('Partner')
                    ->sortable()
                    ->searchable()
                    ->description(fn ($record) => $record->partnership->ownership_percentage.'% ownership'),

                Tables\Columns\BadgeColumn::make('settlement_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => Settlement::getTypeOptions()[$state] ?? $state)
                    ->color(fn ($record) => $record->getTypeColor()),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(fn ($record) => $record->getFormattedAmount())
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => Settlement::getStatusOptions()[$state] ?? $state)
                    ->color(fn ($record) => $record->getStatusColor()),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state ?? '')))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('initiatedBy.name')
                    ->label('Initiated By')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved')
                    ->dateTime('M j, Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Settlement::getStatusOptions()),

                Tables\Filters\SelectFilter::make('settlement_type')
                    ->label('Type')
                    ->options(Settlement::getTypeOptions()),

                Tables\Filters\SelectFilter::make('partnership')
                    ->relationship('partnership', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->store->name} - {$record->user->name}"
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->canBeApproved() &&
                        (Auth::user()->isCompanyOwner() || Auth::user()->isAdmin())
                    )
                    ->form([
                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Payment Reference (Optional)')
                            ->placeholder('Transaction ID, confirmation number, etc.'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $record->approve(Auth::user(), $data['payment_reference'] ?? null);
                            \Filament\Notifications\Notification::make()
                                ->title('Settlement approved successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to approve settlement')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Approve Settlement')
                    ->modalDescription('Are you sure you want to approve this settlement? This will update the partner\'s debt balance.'),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->canBeApproved() &&
                        (Auth::user()->isCompanyOwner() || Auth::user()->isAdmin())
                    )
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3)
                            ->placeholder('Please provide a reason for rejection...'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $record->reject(Auth::user(), $data['rejection_reason']);
                            \Filament\Notifications\Notification::make()
                                ->title('Settlement rejected')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to reject settlement')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('complete')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === 'approved' &&
                        (Auth::user()->isCompanyOwner() || Auth::user()->isAdmin())
                    )
                    ->action(function ($record) {
                        try {
                            $record->markAsCompleted();
                            \Filament\Notifications\Notification::make()
                                ->title('Settlement marked as completed')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to complete settlement')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->isCompanyOwner()),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettlements::route('/'),
            'create' => Pages\CreateSettlement::route('/create'),
            'view' => Pages\ViewSettlement::route('/{record}'),
            'edit' => Pages\EditSettlement::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
