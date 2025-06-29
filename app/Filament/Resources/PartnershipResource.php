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
    
    protected static ?string $navigationLabel = 'Partner Management';
    
    protected static ?string $navigationGroup = 'Partnership';
    
    protected static ?string $modelLabel = 'Partnership';
    
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', Partnership::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', Partnership::class) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isCompanyOwner() || auth()->user()?->isAdmin() || auth()->user()?->isPartner();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Company owners and admins see all partnerships in their company
        if ($user->isCompanyOwner() || $user->isAdmin()) {
            return $query->whereHas('store', function ($storeQuery) use ($user) {
                $storeQuery->where('company_id', $user->company_id);
            });
        }

        // Partners only see their own partnerships
        if ($user->isPartner()) {
            return $query->where('user_id', $user->id);
        }

        return $query->whereNull('id'); // Return empty for other user types
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Partnership Information')
                    ->description('Create a new partnership by selecting a store and partner details.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('store_id')
                                    ->label('Select Store')
                                    ->relationship('store', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if ($state) {
                                            $available = Partnership::getAvailableOwnershipForStore($state);
                                            $set('_available_ownership', $available);
                                        }
                                    })
                                    ->helperText(fn (Get $get) => $get('_available_ownership') 
                                        ? "Available ownership: {$get('_available_ownership')}%" 
                                        : 'Choose a store to see available ownership'),

                                Forms\Components\TextInput::make('ownership_percentage')
                                    ->label('Ownership Percentage')
                                    ->required()
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0.01)
                                    ->maxValue(fn (Get $get) => $get('_available_ownership') ?: 100)
                                    ->step(0.01)
                                    ->placeholder('25.00')
                                    ->helperText('Enter the ownership percentage for this partner'),
                            ]),

                        Forms\Components\Hidden::make('_available_ownership'),
                    ]),

                Forms\Components\Section::make('Partner Details')
                    ->description('Choose how to add the partner - select existing user or invite via email.')
                    ->schema([
                        Forms\Components\Tabs::make('Partner Selection')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Existing Partner')
                                    ->schema([
                                        Forms\Components\Select::make('user_id')
                                            ->label('Choose Existing Partner')
                                            ->relationship('user', 'name', function ($query) {
                                                return $query->where('user_type', 'partner')
                                                           ->where('company_id', auth()->user()->company_id);
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Select a partner who already has an account'),
                                    ]),

                                Forms\Components\Tabs\Tab::make('Invite New Partner')
                                    ->schema([
                                        Forms\Components\TextInput::make('partner_email')
                                            ->label('Partner Email Address')
                                            ->email()
                                            ->placeholder('partner@company.com')
                                            ->helperText('We will send an invitation email to this address'),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Profit Sharing')
                    ->schema([
                        Forms\Components\TextInput::make('profit_share_percentage')
                            ->label('Profit Share Percentage')
                            ->numeric()
                            ->suffix('%')
                            ->default(fn (Get $get) => $get('ownership_percentage'))
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->helperText('Usually matches ownership percentage, but can be different'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Additional Details')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Partnership Status')
                            ->options([
                                'PENDING_INVITATION' => 'Pending Invitation',
                                'ACTIVE' => 'Active',
                                'INACTIVE' => 'Inactive',
                            ])
                            ->default(fn (Get $get) => $get('partner_email') ? 'PENDING_INVITATION' : 'ACTIVE')
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Partnership Notes')
                            ->placeholder('Any special agreements or notes about this partnership...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Partner')
                    ->sortable()
                    ->searchable()
                    ->placeholder(fn ($record) => $record && $record->partner_email ? $record->partner_email : 'No Partner')
                    ->description(fn ($record) => $record && $record->user 
                        ? $record->user->email 
                        : ($record && $record->partner_email ? 'Invitation sent' : null)),
                    
                Tables\Columns\TextColumn::make('ownership_percentage')
                    ->label('Ownership')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 50 => 'success',
                        $state >= 25 => 'warning', 
                        $state >= 10 => 'info',
                        default => 'gray'
                    }),
                    
                Tables\Columns\TextColumn::make('debt_balance')
                    ->label('Debt Balance')
                    ->getStateUsing(fn (Partnership $record) => $record->getFormattedDebtBalance())
                    ->badge()
                    ->color(fn (Partnership $record) => match ($record->getDebtStatus()) {
                        'owes_money' => 'danger',
                        'has_credit' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn (Partnership $record) => match ($record->getDebtStatus()) {
                        'owes_money' => 'heroicon-o-exclamation-triangle',
                        'has_credit' => 'heroicon-o-check-circle',
                        default => null,
                    }),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ACTIVE' => 'Active',
                        'PENDING_INVITATION' => 'Pending',
                        'INACTIVE' => 'Inactive',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'ACTIVE',
                        'warning' => 'PENDING_INVITATION',
                        'danger' => 'INACTIVE',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store')
                    ->label('Filter by Store')
                    ->relationship('store', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'ACTIVE' => 'Active',
                        'PENDING_INVITATION' => 'Pending Invitation',
                        'INACTIVE' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('send_invitation')
                    ->label('Send Invitation')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->visible(fn ($record) => $record && $record->status === 'PENDING_INVITATION' && $record->partner_email)
                    ->action(function ($record) {
                        try {
                            $record->sendInvitationEmail();
                            \Filament\Notifications\Notification::make()
                                ->title('Invitation sent successfully!')
                                ->body("Invitation email sent to {$record->partner_email}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to send invitation')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('resend_invitation')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn ($record) => $record && $record->status === 'PENDING_INVITATION' && $record->partner_email && $record->invited_at)
                    ->action(function ($record) {
                        try {
                            $record->generateInvitationToken();
                            $record->sendInvitationEmail();
                            \Filament\Notifications\Notification::make()
                                ->title('Invitation resent successfully!')
                                ->body("New invitation email sent to {$record->partner_email}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to resend invitation')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

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
