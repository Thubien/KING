<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Partnership;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->whereIn('store_id', auth()->user()->getAccessibleStoreIds())
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label(' Date')
                    ->date('M j')
                    ->sortable(),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->limit(20),

                Tables\Columns\TextColumn::make('description')
                    ->label('📝 Description')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label(' Amount')
                    ->formatStateUsing(fn ($state, $record) => 
                        ($record->type === 'income' ? '+' : '-') . '$' . number_format(abs($state), 2)
                    )
                    ->color(fn ($record) => $record->type === 'income' ? 'success' : 'danger')
                    ->weight('medium'),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('🏷 Category')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->colors([
                        'success' => 'revenue',
                        'warning' => 'operational',
                        'info' => 'marketing',
                        'danger' => 'refunds_returns',
                        'gray' => 'other',
                    ]),
            ])
            ->heading('🕒 Recent Transactions')
            ->description('Latest financial activity across your stores')
            ->headerActions([
                Tables\Actions\Action::make('view_all')
                    ->label('View All Transactions')
                    ->icon('heroicon-o-arrow-right')
                    ->url(route('filament.admin.resources.transactions.index'))
                    ->button(),
            ])
            ->paginated(false);
    }
}