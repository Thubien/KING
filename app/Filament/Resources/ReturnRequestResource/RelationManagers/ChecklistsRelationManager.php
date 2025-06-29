<?php

namespace App\Filament\Resources\ReturnRequestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChecklistsRelationManager extends RelationManager
{
    protected static string $relationship = 'checklists';
    protected static ?string $title = 'Kontrol Listesi';
    protected static ?string $pluralLabel = 'Kontrol Adımları';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('stage')
                    ->label('Aşama')
                    ->options(\App\Models\ReturnRequest::STATUSES)
                    ->required()
                    ->disabled(),
                    
                Forms\Components\TextInput::make('item_text')
                    ->label('Kontrol Adımı')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\Toggle::make('is_checked')
                    ->label('Tamamlandı')
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $set('checked_at', now());
                            $set('checked_by', auth()->id());
                        } else {
                            $set('checked_at', null);
                            $set('checked_by', null);
                        }
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item_text')
            ->columns([
                Tables\Columns\TextColumn::make('stage')
                    ->label('Aşama')
                    ->formatStateUsing(fn ($state) => \App\Models\ReturnRequest::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'pending' => 'gray',
                        'in_transit' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        default => 'gray'
                    }),
                    
                Tables\Columns\TextColumn::make('item_text')
                    ->label('Kontrol Adımı')
                    ->searchable(),
                    
                Tables\Columns\ToggleColumn::make('is_checked')
                    ->label('Tamamlandı')
                    ->afterStateUpdated(function ($record, $state) {
                        $record->update([
                            'checked_at' => $state ? now() : null,
                            'checked_by' => $state ? auth()->id() : null,
                        ]);
                    }),
                    
                Tables\Columns\TextColumn::make('checkedBy.name')
                    ->label('Tamamlayan')
                    ->placeholder('-'),
                    
                Tables\Columns\TextColumn::make('checked_at')
                    ->label('Tamamlanma Zamanı')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at')
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->label('Aşama')
                    ->options(\App\Models\ReturnRequest::STATUSES),
                    
                Tables\Filters\TernaryFilter::make('is_checked')
                    ->label('Durum')
                    ->placeholder('Tümü')
                    ->trueLabel('Tamamlandı')
                    ->falseLabel('Bekliyor'),
            ])
            ->headerActions([
                // Otomatik oluşturulduğu için manuel ekleme kapalı
            ])
            ->actions([
                // Düzenleme ve silme kapalı, sadece toggle ile güncelleme
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_completed')
                    ->label('Tamamlandı Olarak İşaretle')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            $record->update([
                                'is_checked' => true,
                                'checked_at' => now(),
                                'checked_by' => auth()->id(),
                            ]);
                        });
                    })
                    ->deselectRecordsAfterCompletion(),
                    
                Tables\Actions\BulkAction::make('mark_uncompleted')
                    ->label('Tamamlanmadı Olarak İşaretle')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            $record->update([
                                'is_checked' => false,
                                'checked_at' => null,
                                'checked_by' => null,
                            ]);
                        });
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('Kontrol adımı bulunmuyor')
            ->emptyStateDescription('Bu aşama için henüz kontrol adımı oluşturulmamış.');
    }
}