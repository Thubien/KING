<?php

namespace App\Filament\Resources\StoreResource\Pages;

use App\Filament\Resources\StoreResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class ViewStore extends ViewRecord
{
    protected static string $resource = StoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->color('warning'),
            Actions\Action::make('shopify_connect')
                ->label('Shopify\'a Bağlan')
                ->icon('heroicon-o-link')
                ->color('success')
                ->visible(fn ($record) => !$record->shopify_connected),
            Actions\Action::make('view_transactions')
                ->label('İşlemleri Görüntüle')
                ->icon('heroicon-o-banknotes')
                ->url(fn ($record) => route('filament.admin.resources.transactions.index', ['tableFilters[store_id][value]' => $record->id]))
                ->color('info'),
            Actions\Action::make('financial_report')
                ->label('Detaylı Rapor')
                ->icon('heroicon-o-document-chart-bar')
                ->color('gray'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        Group::make([
                            Section::make('Mağaza Bilgileri')
                                ->icon('heroicon-o-building-storefront')
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Mağaza Adı')
                                        ->weight(FontWeight::Bold)
                                        ->size('lg'),
                                    
                                    TextEntry::make('shopify_domain')
                                        ->label('Domain')
                                        ->icon('heroicon-o-globe-alt')
                                        ->url(fn ($state) => $state ? "https://{$state}" : null)
                                        ->openUrlInNewTab()
                                        ->color('info')
                                        ->default('Bağlı değil'),

                                    TextEntry::make('company.name')
                                        ->label('Şirket')
                                        ->icon('heroicon-o-building-office')
                                        ->badge()
                                        ->color('gray'),

                                    TextEntry::make('status')
                                        ->label('Durum')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'active' => 'success',
                                            'inactive' => 'danger',
                                            'suspended' => 'warning',
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state): string => match ($state) {
                                            'active' => 'Aktif',
                                            'inactive' => 'Pasif',
                                            'suspended' => 'Askıda',
                                            default => $state,
                                        }),

                                    TextEntry::make('currency')
                                        ->label('Para Birimi')
                                        ->badge()
                                        ->color('success'),

                                    TextEntry::make('created_at')
                                        ->label('Açılma Tarihi')
                                        ->date('d M Y')
                                        ->icon('heroicon-o-calendar'),
                                ]),
                        ])->columnSpan(1),

                        Group::make([
                            Section::make('Finansal Özet')
                                ->icon('heroicon-o-currency-dollar')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextEntry::make('total_revenue')
                                                ->label('Toplam Gelir')
                                                ->money(fn ($record) => $record->currency)
                                                ->weight(FontWeight::Bold)
                                                ->color('success')
                                                ->size('lg')
                                                ->getStateUsing(function ($record) {
                                                    return $record->transactions()
                                                        ->where('type', 'income')
                                                        ->where('category', 'SALES')
                                                        ->sum('amount');
                                                }),

                                            TextEntry::make('total_expense')
                                                ->label('Toplam Gider')
                                                ->money(fn ($record) => $record->currency)
                                                ->weight(FontWeight::Bold)
                                                ->color('danger')
                                                ->getStateUsing(function ($record) {
                                                    return $record->transactions()
                                                        ->where('type', 'expense')
                                                        ->sum('amount');
                                                }),

                                            TextEntry::make('net_profit')
                                                ->label('Net Kar/Zarar')
                                                ->money(fn ($record) => $record->currency)
                                                ->weight(FontWeight::Bold)
                                                ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                                                ->size('lg')
                                                ->getStateUsing(function ($record) {
                                                    $income = $record->transactions()
                                                        ->where('type', 'income')
                                                        ->sum('amount');
                                                    $expense = $record->transactions()
                                                        ->where('type', 'expense')
                                                        ->sum('amount');
                                                    return $income - $expense;
                                                }),

                                            TextEntry::make('transaction_count')
                                                ->label('Toplam İşlem')
                                                ->numeric()
                                                ->badge()
                                                ->color('gray')
                                                ->getStateUsing(fn ($record) => $record->transactions()->count()),
                                        ]),

                                    Section::make('Aylık Finansal Durum')
                                        ->collapsible()
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    TextEntry::make('monthly_revenue')
                                                        ->label('Bu Ay Gelir')
                                                        ->money(fn ($record) => $record->currency)
                                                        ->color('success')
                                                        ->getStateUsing(function ($record) {
                                                            return $record->transactions()
                                                                ->where('type', 'income')
                                                                ->where('category', 'SALES')
                                                                ->whereMonth('transaction_date', now()->month)
                                                                ->whereYear('transaction_date', now()->year)
                                                                ->sum('amount');
                                                        }),

                                                    TextEntry::make('monthly_expense')
                                                        ->label('Bu Ay Gider')
                                                        ->money(fn ($record) => $record->currency)
                                                        ->color('danger')
                                                        ->getStateUsing(function ($record) {
                                                            return $record->transactions()
                                                                ->where('type', 'expense')
                                                                ->whereMonth('transaction_date', now()->month)
                                                                ->whereYear('transaction_date', now()->year)
                                                                ->sum('amount');
                                                        }),
                                                ]),
                                        ]),
                                ]),
                        ])->columnSpan(1),

                        Group::make([
                            Section::make('Ortaklık Bilgileri')
                                ->icon('heroicon-o-user-group')
                                ->schema([
                                    ViewEntry::make('partnerships')
                                        ->label('')
                                        ->view('filament.infolists.store-partnerships'),
                                ]),

                            Section::make('Son İşlemler')
                                ->icon('heroicon-o-clock')
                                ->headerActions([
                                    Action::make('view_all')
                                        ->label('Tümünü Gör')
                                        ->url(fn ($record) => route('filament.admin.resources.transactions.index', ['tableFilters[store_id][value]' => $record->id]))
                                        ->color('gray')
                                        ->size('sm'),
                                ])
                                ->schema([
                                    ViewEntry::make('recent_transactions')
                                        ->label('')
                                        ->view('filament.infolists.recent-transactions')
                                        ->viewData([
                                            'transactions' => fn ($record) => $record->transactions()
                                                ->with(['customer'])
                                                ->latest('transaction_date')
                                                ->take(5)
                                                ->get(),
                                        ]),
                                ]),
                        ])->columnSpan(1),
                    ]),

                Section::make('Gider Kategorileri Dağılımı')
                    ->icon('heroicon-o-chart-pie')
                    ->collapsible()
                    ->schema([
                        ViewEntry::make('expense_breakdown')
                            ->label('')
                            ->view('filament.infolists.expense-breakdown')
                            ->viewData(function ($record) {
                                $expenses = $record->transactions()
                                    ->where('type', 'expense')
                                    ->selectRaw('category, SUM(amount) as total')
                                    ->groupBy('category')
                                    ->orderByDesc('total')
                                    ->get();

                                return ['expenses' => $expenses, 'currency' => $record->currency];
                            }),
                    ]),

                Section::make('Ortaklar')
                    ->icon('heroicon-o-users')
                    ->collapsible()
                    ->schema([
                        ViewEntry::make('partners_detail')
                            ->label('')
                            ->view('filament.infolists.partners-detail')
                            ->viewData(fn ($record) => [
                                'partnerships' => $record->partnerships()->with('user')->get(),
                                'store' => $record,
                            ]),
                    ]),
            ]);
    }
}