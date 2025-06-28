<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Store;
use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class TopPerformersWidget extends BaseWidget
{
    protected static ?string $heading = ' Top Performers This Month';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        $company = Auth::user()->company;
        
        return $table
            ->query(
                User::query()
                    ->where('company_id', $company->id)
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'sales_rep');
                    })
                    ->withSum(['salesTransactions as monthly_sales' => function ($query) {
                        $query->whereMonth('transaction_date', now()->month)
                              ->whereYear('transaction_date', now()->year);
                    }], 'amount_usd')
                    ->withCount(['salesTransactions as monthly_orders' => function ($query) {
                        $query->whereMonth('transaction_date', now()->month)
                              ->whereYear('transaction_date', now()->year);
                    }])
                    ->orderBy('monthly_sales', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('name')
                            ->weight('bold')
                            ->icon('heroicon-o-user')
                            ->searchable(),
                            
                        Tables\Columns\TextColumn::make('monthly_sales')
                            ->label('Monthly Sales')
                            ->money('USD')
                            ->sortable()
                            ->alignEnd()
                            ->color('success'),
                    ]),
                    
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('monthly_orders')
                            ->label('Orders')
                            ->suffix(' orders')
                            ->icon('heroicon-o-shopping-bag')
                            ->color('info'),
                            
                        Tables\Columns\TextColumn::make('commission_earned')
                            ->label('Commission')
                            ->getStateUsing(function (User $record) {
                                $partnerships = $record->company->partnerships()
                                    ->whereHas('stores.transactions', function ($query) use ($record) {
                                        $query->where('sales_rep_id', $record->id)
                                              ->whereMonth('transaction_date', now()->month)
                                              ->whereYear('transaction_date', now()->year);
                                    })
                                    ->with(['stores.transactions' => function ($query) use ($record) {
                                        $query->where('sales_rep_id', $record->id)
                                              ->whereMonth('transaction_date', now()->month)
                                              ->whereYear('transaction_date', now()->year);
                                    }])
                                    ->get();
                                    
                                $commission = 0;
                                foreach ($partnerships as $partnership) {
                                    foreach ($partnership->stores as $store) {
                                        $storeRevenue = $store->transactions->sum('amount_usd');
                                        $commission += $storeRevenue * ($partnership->sales_rep_percentage / 100);
                                    }
                                }
                                
                                return $commission;
                            })
                            ->money('USD')
                            ->color('warning')
                            ->alignEnd(),
                    ]),
                    
                    Tables\Columns\Layout\Panel::make([
                        Tables\Columns\TextColumn::make('performance_metrics')
                            ->label('')
                            ->getStateUsing(function (User $record) {
                                $totalSales = $record->salesTransactions()->sum('amount_usd');
                                $totalOrders = $record->salesTransactions()->count();
                                $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
                                
                                return " Total Sales: $" . number_format($totalSales, 2) . 
                                       " |  Avg Order: $" . number_format($avgOrderValue, 2) . 
                                       " |  Active Since: " . $record->created_at->format('M Y');
                            })
                            ->color('gray'),
                    ])->collapsed(false),
                ])
            ])
            ->actions([
                Tables\Actions\Action::make('view_profile')
                    ->label('View Profile')
                    ->icon('heroicon-o-eye')
                    ->url(fn (User $record) => route('filament.admin.resources.users.view', $record)),
                    
                Tables\Actions\Action::make('view_transactions')
                    ->label('View Orders')
                    ->icon('heroicon-o-shopping-bag')
                    ->url(fn (User $record) => route('filament.admin.resources.transactions.index', [
                        'tableFilters' => ['sales_rep_id' => ['value' => $record->id]]
                    ])),
            ])
            ->emptyStateHeading('No Sales Reps Found')
            ->emptyStateDescription('Sales representatives will appear here once they start making sales.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->paginated(false);
    }
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user?->isCompanyOwner() || $user?->isAdmin();
    }
}