<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnershipResource\Pages;
use App\Filament\Resources\PartnershipResource\RelationManagers;
use App\Models\Partnership;
use App\Models\Store;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;

class PartnershipResource extends Resource
{
    protected static ?string $model = Partnership::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Partnerships';
    
    protected static ?string $modelLabel = 'Partnership';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Partnership Details')
                    ->schema([
                        Forms\Components\Select::make('store_id')
                            ->label('Store')
                            ->relationship('store', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if ($state) {
                                    $availableOwnership = Partnership::getAvailableOwnershipForStore($state);
                                    $set('available_ownership', $availableOwnership);
                                }
                            })
                            ->helperText('Select the store for this partnership'),
                            
                        Forms\Components\Select::make('user_id')
                            ->label('Partner')
                            ->relationship('user', 'name')
                            ->required()
                            ->helperText('Select the partner user'),
                            
                        Forms\Components\TextInput::make('ownership_percentage')
                            ->label('Ownership Percentage (%)')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->maxValue(100)
                            ->live()
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $storeId = $get('store_id');
                                        if (!$storeId) return;
                                        
                                        $available = Partnership::getAvailableOwnershipForStore($storeId);
                                        if ($value > $available) {
                                            $fail("Maximum available ownership for this store is {$available}%");
                                        }
                                    };
                                },
                            ])
                            ->helperText(function (Get $get) {
                                $storeId = $get('store_id');
                                if (!$storeId) return 'Select a store first';
                                
                                $available = Partnership::getAvailableOwnershipForStore($storeId);
                                $total = Partnership::getTotalOwnershipForStore($storeId);
                                
                                return "Available: {$available}% | Current Total: {$total}%";
                            }),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Role & Permissions')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->required()
                            ->options([
                                'owner' => 'Owner',
                                'partner' => 'Partner', 
                                'investor' => 'Investor',
                                'manager' => 'Manager',
                            ])
                            ->default('partner'),
                            
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'terminated' => 'Terminated',
                            ])
                            ->default('pending'),
                            
                        Forms\Components\Textarea::make('role_description')
                            ->label('Role Description')
                            ->columnSpanFull()
                            ->helperText('Describe the partner\'s specific role and responsibilities'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Partnership Timeline')
                    ->schema([
                        Forms\Components\DatePicker::make('partnership_start_date')
                            ->label('Start Date')
                            ->required()
                            ->default(today()),
                            
                        Forms\Components\DatePicker::make('partnership_end_date')
                            ->label('End Date')
                            ->helperText('Leave empty for indefinite partnership'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->helperText('Any additional notes about this partnership'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Partner')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('ownership_percentage')
                    ->label('Ownership %')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                    ->color(fn ($state) => $state > 50 ? 'success' : ($state > 25 ? 'warning' : 'gray')),
                    
                Tables\Columns\BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'success' => 'owner',
                        'warning' => 'partner',
                        'info' => 'investor',
                        'gray' => 'manager',
                    ]),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'pending',
                        'danger' => 'terminated',
                        'gray' => 'inactive',
                    ]),
                    
                Tables\Columns\TextColumn::make('partnership_start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('partnership_end_date')
                    ->label('End Date')
                    ->date()
                    ->placeholder('Indefinite')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store')
                    ->relationship('store', 'name'),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'pending' => 'Pending',
                        'inactive' => 'Inactive',
                        'terminated' => 'Terminated',
                    ]),
                    
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'owner' => 'Owner',
                        'partner' => 'Partner',
                        'investor' => 'Investor',
                        'manager' => 'Manager',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPartnerships::route('/'),
            'create' => Pages\CreatePartnership::route('/create'),
            'edit' => Pages\EditPartnership::route('/{record}/edit'),
        ];
    }
}
