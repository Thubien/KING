<?php

namespace App\Filament\Pages;

use App\Models\Transaction;
use App\Models\Store;
use App\Models\User;
use App\Services\SmartSuggestionService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class TransactionEditor extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationLabel = 'Transaction Editor';
    protected static ?string $title = 'Transaction Editor';
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.pages.transaction-editor';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->where(function ($query) {
                        $query->whereHas('store', function ($q) {
                            $q->where('company_id', Auth::user()->company_id);
                        })
                        ->orWhereNull('store_id');
                    })
                    ->with(['store', 'partner', 'matchedTransaction', 'splitTransactions'])
                    ->orderBy('transaction_date', 'desc')
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                // Mobile-friendly stack layout
                Stack::make([
                    Split::make([
                        Stack::make([
                            TextColumn::make('transaction_date')
                                ->date('M j, Y')
                                ->weight('medium')
                                ->size('sm'),
                            TextColumn::make('description')
                                ->limit(50)
                                ->tooltip(fn (Transaction $record): string => $record->description)
                                ->searchable()
                                ->weight('medium'),
                        ]),
                        TextColumn::make('amount')
                            ->money(fn (Transaction $record): string => $record->currency)
                            ->color(fn (Transaction $record): string => $record->amount >= 0 ? 'success' : 'danger')
                            ->weight('bold')
                            ->size('lg')
                            ->alignEnd(),
                    ]),
                    Split::make([
                        Stack::make([
                            TextColumn::make('assignment_status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'pending' => 'warning',
                                    'assigned' => 'success',
                                    'split' => 'info',
                                    'matched' => 'primary',
                                    default => 'gray',
                                })
                                ->icon(fn (string $state): string => match ($state) {
                                    'pending' => 'heroicon-o-clock',
                                    'assigned' => 'heroicon-o-check-circle',
                                    'split' => 'heroicon-o-squares-2x2',
                                    'matched' => 'heroicon-o-link',
                                    default => 'heroicon-o-question-mark-circle',
                                }),
                            TextColumn::make('store.name')
                                ->placeholder('Not assigned')
                                ->icon('heroicon-o-building-storefront')
                                ->color('gray'),
                        ]),
                        Stack::make([
                            TextColumn::make('category')
                                ->badge()
                                ->color('gray')
                                ->formatStateUsing(fn (?string $state): string => $state ? (Transaction::CATEGORIES[$state] ?? $state) : 'Not assigned')
                                ->placeholder('Not assigned'),
                            TextColumn::make('subcategory')
                                ->badge()
                                ->color('gray')
                                ->size('xs')
                                ->formatStateUsing(function (?Transaction $record): ?string {
                                    if (!$record || !$record->subcategory || !$record->category) return null;
                                    $subcategories = Transaction::SUBCATEGORIES[$record->category] ?? [];
                                    return $subcategories[$record->subcategory] ?? $record->subcategory;
                                })
                                ->visible(fn (?Transaction $record): bool => $record && $record->subcategory !== null),
                        ])->alignEnd(),
                    ]),
                    TextColumn::make('user_notes')
                        ->color('gray')
                        ->size('sm')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->visible(fn (?Transaction $record): bool => $record && $record->user_notes !== null),
                ])->space(2),
            ])
            ->filters([
                SelectFilter::make('assignment_status')
                    ->options([
                        'pending' => 'Pending',
                        'assigned' => 'Assigned',
                        'split' => 'Split',
                        'matched' => 'Matched',
                    ])
                    ->default('pending'),
                SelectFilter::make('type')
                    ->options([
                        'INCOME' => 'Income (+)',
                        'EXPENSE' => 'Expense (-)',
                    ]),
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->default(now()->subMonth()),
                        DatePicker::make('to')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('transaction_date', '>=', $date))
                            ->when($data['to'], fn ($q, $date) => $q->whereDate('transaction_date', '<=', $date));
                    }),
            ])
            ->actions([
                Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->form(function (Transaction $record) {
                        // Get smart suggestion
                        $suggestionService = new SmartSuggestionService();
                        $suggestion = $suggestionService->suggestAssignment($record);
                        
                        return [
                            // Transaction info at the top
                            \Filament\Forms\Components\Section::make('Transaction Details')
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('transaction_info')
                                        ->label('')
                                        ->content(function () use ($record) {
                                            return new \Illuminate\Support\HtmlString("
                                                <div class='space-y-2'>
                                                    <div class='flex justify-between'>
                                                        <span class='text-sm text-gray-500'>Date:</span>
                                                        <span class='text-sm font-medium'>{$record->transaction_date->format('M j, Y')}</span>
                                                    </div>
                                                    <div class='flex justify-between'>
                                                        <span class='text-sm text-gray-500'>Description:</span>
                                                        <span class='text-sm font-medium'>{$record->description}</span>
                                                    </div>
                                                    <div class='flex justify-between'>
                                                        <span class='text-sm text-gray-500'>Amount:</span>
                                                        <span class='text-sm font-bold " . ($record->amount >= 0 ? 'text-green-600' : 'text-red-600') . "'>{$record->currency} " . number_format(abs($record->amount), 2) . "</span>
                                                    </div>
                                                </div>
                                            ");
                                        }),
                                ])
                                ->collapsible()
                                ->collapsed(false),
                            
                            // Show suggestion if available
                            \Filament\Forms\Components\Section::make()
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('suggestion')
                                        ->label('Smart Suggestion')
                                        ->content(function () use ($suggestion) {
                                            if (!$suggestion) {
                                                return 'No suggestion available';
                                            }
                                            
                                            $store = Store::find($suggestion['store_id']);
                                            $storeName = $store ? $store->name : 'Select store';
                                            $category = Transaction::CATEGORIES[$suggestion['category']] ?? $suggestion['category'];
                                            
                                            return "Store: {$storeName} | Category: {$category} | Confidence: {$suggestion['confidence']}%";
                                        }),
                                    \Filament\Forms\Components\Placeholder::make('reason')
                                        ->label('')
                                        ->content(fn () => $suggestion['suggestion_reason'] ?? '')
                                        ->visible(fn () => $suggestion !== null),
                                ])
                                ->visible(fn () => $suggestion !== null)
                                ->collapsible(),
                                
                            Select::make('store_id')
                                ->label('Store')
                                ->options(Store::where('company_id', Auth::user()->company_id)->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->default($suggestion['store_id'] ?? null),
                            Select::make('category')
                                ->label('Category')
                                ->options(fn (Transaction $record) => $record->amount >= 0 
                                    ? Transaction::getIncomeCategories() 
                                    : array_diff_key(Transaction::CATEGORIES, Transaction::getIncomeCategories()))
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn ($state, callable $set) => $set('subcategory', null))
                                ->default($suggestion['category'] ?? null),
                            Select::make('subcategory')
                                ->label('Subcategory')
                                ->options(fn (callable $get) => Transaction::SUBCATEGORIES[$get('category')] ?? [])
                                ->visible(fn (callable $get): bool => array_key_exists($get('category'), Transaction::SUBCATEGORIES))
                                ->default($suggestion['subcategory'] ?? null),
                            Toggle::make('is_personal_expense')
                                ->label('Personal Expense')
                                ->reactive()
                                ->visible(fn (?Transaction $record): bool => $record && $record->amount < 0),
                            Select::make('partner_id')
                                ->label('Partner')
                                ->options(User::whereHas('partnerships', function ($query) {
                                    $query->whereHas('store', function ($q) {
                                        $q->where('company_id', Auth::user()->company_id);
                                    });
                                })->pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn (callable $get): bool => $get('is_personal_expense') === true)
                                ->requiredIf('is_personal_expense', true),
                            TextInput::make('user_notes')
                                ->label('Notes')
                                ->placeholder('Optional notes about this transaction'),
                        ];
                    })
                    ->action(function (Transaction $record, array $data): void {
                        $record->update([
                            'store_id' => $data['store_id'],
                            'category' => $data['category'],
                            'subcategory' => $data['subcategory'] ?? null,
                            'is_personal_expense' => $data['is_personal_expense'] ?? false,
                            'partner_id' => $data['partner_id'] ?? null,
                            'user_notes' => $data['user_notes'] ?? null,
                            'assignment_status' => 'assigned',
                        ]);
                        
                        // Learn from this assignment
                        $suggestionService = new SmartSuggestionService();
                        $suggestionService->learnFromAssignment($record);

                        Notification::make()
                            ->title('Transaction assigned')
                            ->body('System learned from your assignment')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (?Transaction $record): bool => $record && $record->assignment_status === 'pending'),
                    
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->form(function (Transaction $record) {
                        return [
                            \Filament\Forms\Components\Section::make('Transaction Details')
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('transaction_info')
                                        ->label('')
                                        ->content(function () use ($record) {
                                            return new \Illuminate\Support\HtmlString("
                                                <div class='space-y-2'>
                                                    <div class='flex justify-between'>
                                                        <span class='text-sm text-gray-500'>Date:</span>
                                                        <span class='text-sm font-medium'>{$record->transaction_date->format('M j, Y')}</span>
                                                    </div>
                                                    <div class='flex justify-between'>
                                                        <span class='text-sm text-gray-500'>Description:</span>
                                                        <span class='text-sm font-medium'>{$record->description}</span>
                                                    </div>
                                                    <div class='flex justify-between'>
                                                        <span class='text-sm text-gray-500'>Amount:</span>
                                                        <span class='text-sm font-bold " . ($record->amount >= 0 ? 'text-green-600' : 'text-red-600') . "'>{$record->currency} " . number_format(abs($record->amount), 2) . "</span>
                                                    </div>
                                                </div>
                                            ");
                                        }),
                                ])
                                ->collapsible()
                                ->collapsed(false),
                                
                            Select::make('store_id')
                                ->label('Store')
                                ->options(Store::where('company_id', Auth::user()->company_id)->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->default($record->store_id),
                            Select::make('category')
                                ->label('Category')
                                ->options(fn (Transaction $record) => $record->amount >= 0 
                                    ? Transaction::getIncomeCategories() 
                                    : array_diff_key(Transaction::CATEGORIES, Transaction::getIncomeCategories()))
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn ($state, callable $set) => $set('subcategory', null))
                                ->default($record->category),
                            Select::make('subcategory')
                                ->label('Subcategory')
                                ->options(fn (callable $get) => Transaction::SUBCATEGORIES[$get('category')] ?? [])
                                ->visible(fn (callable $get): bool => array_key_exists($get('category'), Transaction::SUBCATEGORIES))
                                ->default($record->subcategory),
                            Toggle::make('is_personal_expense')
                                ->label('Personal Expense')
                                ->reactive()
                                ->visible(fn (?Transaction $record): bool => $record && $record->amount < 0)
                                ->default($record->is_personal_expense),
                            Select::make('partner_id')
                                ->label('Partner')
                                ->options(User::whereHas('partnerships', function ($query) {
                                    $query->whereHas('store', function ($q) {
                                        $q->where('company_id', Auth::user()->company_id);
                                    });
                                })->pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn (callable $get): bool => $get('is_personal_expense') === true)
                                ->requiredIf('is_personal_expense', true)
                                ->default($record->partner_id),
                            TextInput::make('user_notes')
                                ->label('Notes')
                                ->placeholder('Optional notes about this transaction')
                                ->default($record->user_notes),
                        ];
                    })
                    ->action(function (Transaction $record, array $data): void {
                        $record->update([
                            'store_id' => $data['store_id'],
                            'category' => $data['category'],
                            'subcategory' => $data['subcategory'] ?? null,
                            'is_personal_expense' => $data['is_personal_expense'] ?? false,
                            'partner_id' => $data['partner_id'] ?? null,
                            'user_notes' => $data['user_notes'] ?? null,
                        ]);
                        
                        // Learn from edit
                        $suggestionService = new SmartSuggestionService();
                        $suggestionService->learnFromAssignment($record);

                        Notification::make()
                            ->title('Transaction updated')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (?Transaction $record): bool => $record && $record->assignment_status === 'assigned'),
                
                Action::make('split')
                    ->label('Split')
                    ->icon('heroicon-o-squares-2x2')
                    ->color('info')
                    ->url(fn (Transaction $record): string => TransactionSplit::getUrl(['transaction' => $record]))
                    ->visible(fn (?Transaction $record): bool => $record && $record->assignment_status === 'pending' && $record->amount < 0),
                
                Action::make('match')
                    ->label('Match Transfer')
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->visible(fn (?Transaction $record): bool => $record && $record->assignment_status === 'pending' && !$record->matched_transaction_id),
            ])
            ->bulkActions([
                BulkAction::make('bulk_assign')
                    ->label('Bulk Assign')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Select::make('store_id')
                            ->label('Store')
                            ->options(Store::where('company_id', Auth::user()->company_id)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->helperText('Select store for all selected transactions'),
                        Select::make('category')
                            ->label('Category')
                            ->options(function () {
                                // Show all categories in bulk assign since we'll filter by transaction type
                                return Transaction::CATEGORIES;
                            })
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('subcategory', null)),
                        Select::make('subcategory')
                            ->label('Subcategory')
                            ->options(fn (callable $get) => Transaction::SUBCATEGORIES[$get('category')] ?? [])
                            ->visible(fn (callable $get): bool => array_key_exists($get('category'), Transaction::SUBCATEGORIES)),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $suggestionService = new SmartSuggestionService();
                        $assigned = 0;
                        $skipped = 0;
                        
                        $records->each(function (Transaction $record) use ($data, $suggestionService, &$assigned, &$skipped) {
                            // Only update if same type (income/expense)
                            $isIncomeCategory = Transaction::isIncomeCategory($data['category']);
                            if (($record->amount >= 0 && $isIncomeCategory) || 
                                ($record->amount < 0 && !$isIncomeCategory)) {
                                $record->update([
                                    'store_id' => $data['store_id'],
                                    'category' => $data['category'],
                                    'subcategory' => $data['subcategory'] ?? null,
                                    'assignment_status' => 'assigned',
                                ]);
                                
                                // Learn from bulk assignment
                                $suggestionService->learnFromAssignment($record);
                                $assigned++;
                            } else {
                                $skipped++;
                            }
                        });

                        Notification::make()
                            ->title("Bulk assignment completed")
                            ->body("Assigned: {$assigned} transactions" . ($skipped > 0 ? ", Skipped: {$skipped} (wrong type)" : ""))
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                    
                BulkAction::make('bulk_assign_by_pattern')
                    ->label('Smart Assign by Pattern')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Smart Pattern Assignment')
                    ->modalDescription('This will analyze transaction descriptions and assign categories based on patterns.')
                    ->action(function (Collection $records): void {
                        $suggestionService = new SmartSuggestionService();
                        $assigned = 0;
                        $noSuggestion = 0;
                        
                        $records->each(function (Transaction $record) use ($suggestionService, &$assigned, &$noSuggestion) {
                            if ($record->assignment_status === 'pending') {
                                $suggestion = $suggestionService->suggestAssignment($record);
                                
                                if ($suggestion && $suggestion['confidence'] >= 75) {
                                    $record->update([
                                        'store_id' => $suggestion['store_id'],
                                        'category' => $suggestion['category'],
                                        'subcategory' => $suggestion['subcategory'] ?? null,
                                        'assignment_status' => 'assigned',
                                        'user_notes' => 'Auto-assigned with ' . $suggestion['confidence'] . '% confidence',
                                    ]);
                                    
                                    $suggestionService->learnFromAssignment($record);
                                    $assigned++;
                                } else {
                                    $noSuggestion++;
                                }
                            }
                        });
                        
                        Notification::make()
                            ->title('Smart assignment completed')
                            ->body("Assigned: {$assigned} transactions" . ($noSuggestion > 0 ? ", No suggestion for: {$noSuggestion}" : ""))
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->poll('60s')
            ->striped()
            ->paginated(false);
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    public function getHeaderStats(): array
    {
        $query = Transaction::query()
            ->whereHas('store', function ($q) {
                $q->where('company_id', Auth::user()->company_id);
            })
            ->orWhereNull('store_id');
        
        $total = $query->count();
        $pending = $query->clone()->where('assignment_status', 'pending')->count();
        $assigned = $query->clone()->where('assignment_status', 'assigned')->count();
        
        $progress = $total > 0 ? round(($assigned / $total) * 100) : 0;
        
        return [
            'total' => number_format($total),
            'pending' => number_format($pending),
            'assigned' => number_format($assigned),
            'progress' => $progress,
        ];
    }

    public function assignAllFacebookAds(): void
    {
        $transactions = Transaction::query()
            ->whereNull('store_id') // Only pending transactions without store
            ->where('assignment_status', 'pending')
            ->where('amount', '<', 0)
            ->where(function ($query) {
                $query->where('description', 'like', '%facebook%')
                    ->orWhere('description', 'like', '%fb%')
                    ->orWhere('description', 'like', '%meta%');
            })
            ->get();

        $stores = Store::where('company_id', Auth::user()->company_id)->get();
        
        if ($stores->isEmpty()) {
            Notification::make()
                ->title('No stores found')
                ->danger()
                ->send();
            return;
        }

        // Use first store as default for facebook ads
        $defaultStore = $stores->first();
        
        $count = 0;
        foreach ($transactions as $transaction) {
            $transaction->update([
                'store_id' => $defaultStore->id,
                'category' => 'ADS',
                'subcategory' => 'FACEBOOK',
                'assignment_status' => 'assigned',
            ]);
            $count++;
        }

        Notification::make()
            ->title("Assigned {$count} Facebook ads transactions")
            ->success()
            ->send();

        $this->resetTable();
    }

    public function matchTransfers(): void
    {
        $transfers = Transaction::query()
            ->whereNull('store_id') // Only pending transactions without store
            ->where('assignment_status', 'pending')
            ->whereNull('matched_transaction_id')
            ->get();

        $matched = 0;
        
        foreach ($transfers as $transaction) {
            // Look for opposite transaction within 3 days
            $oppositeAmount = -$transaction->amount;
            $startDate = $transaction->transaction_date->copy()->subDays(3);
            $endDate = $transaction->transaction_date->copy()->addDays(3);
            
            $match = Transaction::query()
                ->where('id', '!=', $transaction->id)
                ->whereNull('matched_transaction_id')
                ->whereBetween('amount', [$oppositeAmount * 0.98, $oppositeAmount * 1.02]) // 2% tolerance
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->first();
                
            if ($match) {
                $transaction->update([
                    'matched_transaction_id' => $match->id,
                    'assignment_status' => 'matched',
                    'is_transfer' => true,
                ]);
                
                $match->update([
                    'matched_transaction_id' => $transaction->id,
                    'assignment_status' => 'matched',
                    'is_transfer' => true,
                ]);
                
                $matched++;
            }
        }

        Notification::make()
            ->title("Matched {$matched} transfer pairs")
            ->success()
            ->send();

        $this->resetTable();
    }
}
