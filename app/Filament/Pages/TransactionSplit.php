<?php

namespace App\Filament\Pages;

use App\Models\Store;
use App\Models\Transaction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionSplit extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Split Transaction';

    protected static ?string $title = 'Split Transaction Across Stores';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.transaction-split';

    public Transaction $transaction;

    public array $splits = [];

    public function mount(Transaction $transaction): void
    {
        if ($transaction->assignment_status !== 'pending') {
            redirect()->route('filament.admin.pages.transaction-editor');

            return;
        }

        $this->transaction = $transaction;

        // Initialize with equal split across all stores
        $stores = Store::where('company_id', Auth::user()->company_id)->get();
        $splitPercentage = 100 / $stores->count();

        $this->splits = $stores->map(function ($store) use ($splitPercentage) {
            return [
                'store_id' => $store->id,
                'percentage' => round($splitPercentage, 2),
                'amount' => round($this->transaction->amount * ($splitPercentage / 100), 2),
                'category' => null,
                'subcategory' => null,
                'notes' => null,
            ];
        })->toArray();

        $this->form->fill(['splits' => $this->splits]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('splits')
                    ->label('Store Allocations')
                    ->schema([
                        Select::make('store_id')
                            ->label('Store')
                            ->options(Store::where('company_id', Auth::user()->company_id)->pluck('name', 'id'))
                            ->required()
                            ->disabled()
                            ->columnSpan(2),
                        TextInput::make('percentage')
                            ->label('Percentage')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $percentage = floatval($state) ?: 0;
                                $amount = round($this->transaction->amount * ($percentage / 100), 2);
                                $set('amount', $amount);
                            })
                            ->columnSpan(1),
                        TextInput::make('amount')
                            ->label('Amount')
                            ->disabled()
                            ->prefix($this->transaction->currency)
                            ->columnSpan(1),
                        Select::make('category')
                            ->label('Category')
                            ->options(Transaction::CATEGORIES)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('subcategory', null))
                            ->columnSpan(2),
                        Select::make('subcategory')
                            ->label('Subcategory')
                            ->options(fn (callable $get) => Transaction::SUBCATEGORIES[$get('category')] ?? [])
                            ->visible(fn (callable $get): bool => in_array($get('category'), ['BANK_FEE', 'ADS', 'OTHER_PAY']))
                            ->columnSpan(2),
                        TextInput::make('notes')
                            ->label('Notes')
                            ->placeholder('Optional notes for this allocation')
                            ->columnSpan(4),
                    ])
                    ->columns(4)
                    ->disableItemCreation()
                    ->disableItemDeletion()
                    ->disableItemMovement()
                    ->collapsible()
                    ->defaultItems(count($this->splits)),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Validate percentages sum to 100
        $totalPercentage = collect($data['splits'])->sum('percentage');
        if (abs($totalPercentage - 100) > 0.01) {
            Notification::make()
                ->title('Invalid allocation')
                ->body("Percentages must sum to 100%. Current total: {$totalPercentage}%")
                ->danger()
                ->send();

            return;
        }

        DB::transaction(function () use ($data) {
            // Mark original transaction as split
            $this->transaction->update([
                'assignment_status' => 'split',
                'is_split' => true,
            ]);

            // Create split transactions
            foreach ($data['splits'] as $split) {
                if ($split['percentage'] > 0) {
                    Transaction::create([
                        'parent_transaction_id' => $this->transaction->id,
                        'store_id' => $split['store_id'],
                        'amount' => $split['amount'],
                        'currency' => $this->transaction->currency,
                        'type' => $this->transaction->type,
                        'category' => $split['category'],
                        'subcategory' => $split['subcategory'] ?? null,
                        'description' => $this->transaction->description.' (Split)',
                        'transaction_date' => $this->transaction->transaction_date,
                        'status' => 'completed',
                        'assignment_status' => 'assigned',
                        'source' => $this->transaction->source,
                        'created_by' => Auth::id(),
                        'split_percentage' => $split['percentage'],
                        'user_notes' => $split['notes'] ?? null,
                        'is_split' => true,
                    ]);
                }
            }
        });

        Notification::make()
            ->title('Transaction split successfully')
            ->success()
            ->send();

        redirect()->route('filament.admin.pages.transaction-editor');
    }

    public function cancel(): void
    {
        redirect()->route('filament.admin.pages.transaction-editor');
    }
}
