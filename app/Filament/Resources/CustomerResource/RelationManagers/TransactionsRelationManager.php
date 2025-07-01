<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';
    
    protected static ?string $title = 'Siparişler';
    
    protected static ?string $modelLabel = 'Sipariş';
    
    protected static ?string $pluralModelLabel = 'Siparişler';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tarih')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('İşlem No')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Açıklama')
                    ->limit(30)
                    ->tooltip(fn (Model $record): ?string => $record->description),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Tutar')
                    ->money(fn (Model $record) => strtolower($record->currency))
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'success' => 'APPROVED',
                        'warning' => 'PENDING',
                        'danger' => 'REJECTED',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'APPROVED' => 'Onaylandı',
                        'PENDING' => 'Beklemede',
                        'REJECTED' => 'Reddedildi',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('sales_channel')
                    ->label('Kanal')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'shopify' => 'Shopify',
                        'instagram' => 'Instagram',
                        'telegram' => 'Telegram',
                        'whatsapp' => 'WhatsApp',
                        'facebook' => 'Facebook',
                        'physical' => 'Fiziksel',
                        'referral' => 'Referans',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Ödeme')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'cash' => 'Nakit',
                        'credit_card' => 'Kredi Kartı',
                        'bank_transfer' => 'Havale/EFT',
                        'cash_on_delivery' => 'Kapıda Ödeme',
                        'cargo_collect' => 'Kargo Tahsilatlı',
                        'crypto' => 'Kripto Para',
                        'installment' => 'Taksitli',
                        'store_credit' => 'Mağaza Kredisi',
                        default => $state ?? '—',
                    })
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'APPROVED' => 'Onaylandı',
                        'PENDING' => 'Beklemede',
                        'REJECTED' => 'Reddedildi',
                    ]),
                Tables\Filters\SelectFilter::make('sales_channel')
                    ->label('Satış Kanalı')
                    ->options(\App\Models\Transaction::SALES_CHANNELS),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Ödeme Yöntemi')
                    ->options(\App\Models\Transaction::PAYMENT_METHODS),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Model $record): string => route('filament.admin.resources.transactions.edit', $record)),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->where('category', 'SALES')->where('type', 'income'));
    }
}