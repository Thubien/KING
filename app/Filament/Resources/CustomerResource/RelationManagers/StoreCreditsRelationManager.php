<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StoreCreditsRelationManager extends RelationManager
{
    protected static string $relationship = 'storeCredits';
    
    protected static ?string $title = 'Mağaza Kredileri';
    
    protected static ?string $modelLabel = 'Kredi';
    
    protected static ?string $pluralModelLabel = 'Krediler';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kod')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Tutar')
                    ->money(fn (Model $record) => strtolower($record->currency))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Kalan')
                    ->money(fn (Model $record) => strtolower($record->currency))
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn (Model $record): string => 
                        $record->remaining_amount > 0 ? 'success' : 'gray'
                    ),
                Tables\Columns\TextColumn::make('usage_percentage')
                    ->label('Kullanım')
                    ->getStateUsing(fn (Model $record): string => $record->usage_percentage . '%')
                    ->badge()
                    ->color(fn (Model $record): string => match(true) {
                        $record->usage_percentage >= 100 => 'gray',
                        $record->usage_percentage >= 50 => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'success' => 'active',
                        'info' => 'partially_used',
                        'gray' => 'fully_used',
                        'danger' => 'expired',
                        'warning' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'active' => 'Aktif',
                        'partially_used' => 'Kısmen Kullanıldı',
                        'fully_used' => 'Tamamen Kullanıldı',
                        'expired' => 'Süresi Doldu',
                        'cancelled' => 'İptal Edildi',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('issued_at')
                    ->label('Verilme Tarihi')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Son Kullanım')
                    ->date('d/m/Y')
                    ->color(fn (?Model $record): string => 
                        $record && $record->expires_at && $record->expires_at->isPast() 
                            ? 'danger' 
                            : 'gray'
                    ),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Son Kullanım')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('returnRequest.id')
                    ->label('İade No')
                    ->formatStateUsing(fn (?int $state): string => $state ? "#{$state}" : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'active' => 'Aktif',
                        'partially_used' => 'Kısmen Kullanıldı',
                        'fully_used' => 'Tamamen Kullanıldı',
                        'expired' => 'Süresi Doldu',
                        'cancelled' => 'İptal Edildi',
                    ]),
                Tables\Filters\Filter::make('active_only')
                    ->label('Sadece Aktif Krediler')
                    ->query(fn ($query) => $query->active()),
            ])
            ->actions([
                Tables\Actions\Action::make('view_usage')
                    ->label('Kullanım Geçmişi')
                    ->icon('heroicon-m-list-bullet')
                    ->color('info')
                    ->modalHeading(fn (Model $record): string => "{$record->code} - Kullanım Geçmişi")
                    ->modalContent(fn (Model $record): \Illuminate\View\View => view(
                        'filament.modals.store-credit-usage',
                        ['credit' => $record]
                    ))
                    ->modalSubmitAction(false),
            ])
            ->defaultSort('issued_at', 'desc');
    }
}