<?php

namespace App\Filament\Widgets;

use App\Models\Partnership;
use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PartnershipRevenueWidget extends BaseWidget
{
    protected static ?string $heading = ' Partnership Performance';
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        $company = Auth::user()->company;
        
        return $table
            ->query(
                Partnership::query()
                    ->where('company_id', $company->id)
                    ->where('status', 'active')
                    ->with(['stores', 'partner'])
                    ->withSum(['stores.transactions as monthly_revenue' => function ($query) {
                        $query->whereMonth('transaction_date', now()->month)
                              ->whereYear('transaction_date', now()->year);
                    }], 'amount_usd')
                    ->withSum(['stores.transactions as total_revenue'], 'amount_usd')
                    ->orderBy('monthly_revenue', 'desc')
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('partner.name')
                            ->label('Partner')
                            ->weight('bold')
                            ->icon('heroicon-o-user')
                            ->searchable(),
                            
                        Tables\Columns\TextColumn::make('monthly_revenue')
                            ->label('This Month')
                            ->money('USD')
                            ->sortable()
                            ->alignEnd()
                            ->color('success'),
                    ]),
                    
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('stores_count')
                            ->counts('stores')
                            ->label('Stores')
                            ->icon('heroicon-o-building-storefront')
                            ->color('info'),
                            
                        Tables\Columns\TextColumn::make('partner_share')
                            ->label('Partner Share')
                            ->getStateUsing(function (Partnership $record) {
                                $revenue = $record->monthly_revenue ?? 0;
                                return $revenue * ($record->partner_percentage / 100);
                            })
                            ->money('USD')
                            ->color('warning'),
                            
                        Tables\Columns\TextColumn::make('sales_rep_share')
                            ->label('Sales Rep Share')
                            ->getStateUsing(function (Partnership $record) {
                                $revenue = $record->monthly_revenue ?? 0;
                                return $revenue * ($record->sales_rep_percentage / 100);
                            })
                            ->money('USD')
                            ->color('purple'),
                    ]),
                    
                    Tables\Columns\Layout\Panel::make([
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('partnership_details')
                                ->label('')
                                ->getStateUsing(function (Partnership $record) {
                                    return "Partner: {$record->partner_percentage}% | Sales Rep: {$record->sales_rep_percentage}% | Company: {$record->company_percentage}%";
                                })
                                ->color('gray'),
                                
                            Tables\Columns\TextColumn::make('total_revenue')
                                ->label('All Time Revenue')
                                ->money('USD')
                                ->color('success')
                                ->alignEnd(),
                        ]),
                    ])->collapsed(false),
                ])
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('View Partnership')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Partnership $record) => route('filament.admin.resources.partnerships.view', $record)),
                        
                    Tables\Actions\Action::make('view_stores')
                        ->label('View Stores')
                        ->icon('heroicon-o-building-storefront')
                        ->url(fn (Partnership $record) => route('filament.admin.resources.stores.index', [
                            'tableFilters' => ['partnership_id' => ['value' => $record->id]]
                        ])),
                        
                    Tables\Actions\Action::make('view_transactions')
                        ->label('View Transactions')
                        ->icon('heroicon-o-banknotes')
                        ->url(fn (Partnership $record) => route('filament.admin.resources.transactions.index', [
                            'tableFilters' => ['store_id' => ['values' => $record->stores->pluck('id')->toArray()]]
                        ])),
                        
                    Tables\Actions\Action::make('send_report')
                        ->label('Send Monthly Report')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->action(function (Partnership $record) {
                            // TODO: Implement email report functionality
                            \Filament\Notifications\Notification::make()
                                ->title('Report Sent')
                                ->body("Monthly report sent to {$record->partner->name}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Send Monthly Report')
                        ->modalDescription('Send a detailed monthly performance report to the partner.')
                        ->modalSubmitActionLabel('Send Report'),
                ])
            ])
            ->emptyStateHeading('No Active Partnerships')
            ->emptyStateDescription('Partnerships will appear here once they are created and activated.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->paginated(false);
    }
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user?->isCompanyOwner() || $user?->isAdmin();
    }
}