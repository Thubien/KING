<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';
    
    protected static ?string $title = 'Adresler';
    
    protected static ?string $modelLabel = 'Adres';
    
    protected static ?string $pluralModelLabel = 'Adresler';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('label')
                            ->label('Adres Başlığı')
                            ->placeholder('Ev, İş, vb.')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Adres Tipi')
                            ->options([
                                'billing' => 'Fatura Adresi',
                                'shipping' => 'Teslimat Adresi',
                                'both' => 'Her İkisi',
                            ])
                            ->default('both')
                            ->required(),
                    ]),
                Forms\Components\TextInput::make('full_name')
                    ->label('Alıcı Adı')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address_line_1')
                    ->label('Adres')
                    ->required()
                    ->rows(2)
                    ->maxLength(500),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('district')
                            ->label('İlçe')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('city')
                            ->label('Şehir')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('Posta Kodu')
                            ->maxLength(20),
                    ]),
                Forms\Components\Select::make('country')
                    ->label('Ülke')
                    ->options([
                        'TR' => 'Türkiye',
                        'US' => 'Amerika Birleşik Devletleri',
                        'GB' => 'İngiltere',
                        'DE' => 'Almanya',
                        'FR' => 'Fransa',
                    ])
                    ->default('TR')
                    ->required()
                    ->searchable(),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Varsayılan Adres')
                            ->default(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Başlık')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Alıcı')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address_line_1')
                    ->label('Adres')
                    ->limit(30)
                    ->tooltip(fn (Model $record): string => 
                        $record->address_line_1 . 
                        ($record->address_line_2 ? "\n" . $record->address_line_2 : '') .
                        "\n{$record->district}" .
                        "\n{$record->city}" . ($record->state_province ? ", {$record->state_province}" : '') .
                        " {$record->postal_code}" .
                        "\n{$record->country}"
                    ),
                Tables\Columns\TextColumn::make('city')
                    ->label('Şehir'),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tip')
                    ->colors([
                        'info' => 'billing',
                        'success' => 'shipping',
                        'warning' => 'both',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'billing' => 'Fatura',
                        'shipping' => 'Teslimat',
                        'both' => 'Her İkisi',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Varsayılan')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('country')
                    ->label('Ülke')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'TR' => '🇹🇷 Türkiye',
                        'US' => '🇺🇸 ABD',
                        'GB' => '🇬🇧 İngiltere',
                        'DE' => '🇩🇪 Almanya',
                        'FR' => '🇫🇷 Fransa',
                        default => $state,
                    })
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Adres Tipi')
                    ->options([
                        'billing' => 'Fatura Adresi',
                        'shipping' => 'Teslimat Adresi',
                        'both' => 'Her İkisi',
                    ]),
                Tables\Filters\Filter::make('is_default')
                    ->label('Varsayılan Adresler')
                    ->query(fn ($query) => $query->where('is_default', true)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('set_default')
                    ->label('Varsayılan Yap')
                    ->icon('heroicon-m-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => !$record->is_default)
                    ->action(function (Model $record): void {
                        // Remove default from other addresses
                        $record->customer->addresses()
                            ->where('id', '!=', $record->id)
                            ->update(['is_default' => false]);
                        
                        // Set this as default
                        $record->update(['is_default' => true]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Varsayılan adres güncellendi')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_default', 'desc');
    }
}