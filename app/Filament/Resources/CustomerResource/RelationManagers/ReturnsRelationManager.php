<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReturnsRelationManager extends RelationManager
{
    protected static string $relationship = 'returnRequests';
    
    protected static ?string $title = 'İadeler';
    
    protected static ?string $modelLabel = 'İade';
    
    protected static ?string $pluralModelLabel = 'İadeler';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tarih')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Sipariş No')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Ürün')
                    ->limit(30)
                    ->tooltip(fn (Model $record): ?string => $record->product_name),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Adet')
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('refund_amount')
                    ->label('İade Tutarı')
                    ->money(fn (Model $record) => strtolower($record->currency))
                    ->alignEnd()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'gray' => 'pending',
                        'yellow' => 'in_transit',
                        'blue' => 'processing',
                        'green' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Beklemede',
                        'in_transit' => 'Yolda',
                        'processing' => 'İşlemde',
                        'completed' => 'Tamamlandı',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('resolution')
                    ->label('Çözüm')
                    ->colors([
                        'success' => 'refund',
                        'info' => 'exchange',
                        'warning' => 'store_credit',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'refund' => 'Para İadesi',
                        'exchange' => 'Değişim',
                        'store_credit' => 'Mağaza Kredisi',
                        'rejected' => 'Reddedildi',
                        default => '—',
                    }),
                Tables\Columns\TextColumn::make('return_reason')
                    ->label('İade Sebebi')
                    ->limit(20)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('store_credit_code')
                    ->label('Kredi Kodu')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(\App\Models\ReturnRequest::STATUSES),
                Tables\Filters\SelectFilter::make('resolution')
                    ->label('Çözüm')
                    ->options(\App\Models\ReturnRequest::RESOLUTIONS),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Model $record): string => route('filament.admin.resources.return-requests.edit', $record)),
            ])
            ->defaultSort('created_at', 'desc');
    }
}