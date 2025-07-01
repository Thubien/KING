<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
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

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->color('warning'),
            Actions\Action::make('upgrade_plan')
                ->label('Plan Yükselt')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->visible(fn ($record) => $record->plan !== 'enterprise'),
            Actions\Action::make('view_stores')
                ->label('Mağazaları Gör')
                ->icon('heroicon-o-building-storefront')
                ->url(fn ($record) => route('filament.admin.resources.stores.index', ['tableFilters[company_id][value]' => $record->id]))
                ->color('info'),
            Actions\Action::make('financial_overview')
                ->label('Finansal Özet')
                ->icon('heroicon-o-chart-bar')
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
                            Section::make('Şirket Bilgileri')
                                ->icon('heroicon-o-building-office')
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Şirket Adı')
                                        ->weight(FontWeight::Bold)
                                        ->size('lg'),
                                    
                                    TextEntry::make('domain')
                                        ->label('Domain')
                                        ->icon('heroicon-o-globe-alt')
                                        ->url(fn ($state) => $state ? "https://{$state}" : null)
                                        ->openUrlInNewTab()
                                        ->color('info'),

                                    TextEntry::make('plan')
                                        ->label('Mevcut Plan')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'free' => 'gray',
                                            'starter' => 'info',
                                            'professional' => 'success',
                                            'enterprise' => 'warning',
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state): string => match ($state) {
                                            'free' => 'Ücretsiz',
                                            'starter' => 'Başlangıç',
                                            'professional' => 'Profesyonel',
                                            'enterprise' => 'Kurumsal',
                                            default => $state,
                                        }),

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
                                        ->label('Ana Para Birimi')
                                        ->badge()
                                        ->color('primary'),

                                    TextEntry::make('created_at')
                                        ->label('Kuruluş Tarihi')
                                        ->date('d M Y')
                                        ->icon('heroicon-o-calendar'),

                                    TextEntry::make('trial_ends_at')
                                        ->label('Deneme Bitiş')
                                        ->date('d M Y')
                                        ->icon('heroicon-o-clock')
                                        ->visible(fn ($record) => $record->is_trial)
                                        ->color(fn ($record) => $record->isTrialExpired() ? 'danger' : 'warning'),
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

                                            TextEntry::make('store_count')
                                                ->label('Toplam Mağaza')
                                                ->numeric()
                                                ->badge()
                                                ->color('primary')
                                                ->getStateUsing(fn ($record) => $record->stores()->count()),
                                        ]),

                                    Section::make('Banka & Ödeme Hesapları')
                                        ->collapsible()
                                        ->schema([
                                            ViewEntry::make('accounts_summary')
                                                ->label('')
                                                ->view('filament.infolists.company-accounts-summary')
                                                ->viewData(fn ($record) => [
                                                    'bankAccounts' => $record->bankAccounts,
                                                    'paymentProcessors' => $record->paymentProcessorAccounts,
                                                    'currency' => $record->currency,
                                                ]),
                                        ]),
                                ]),

                            Section::make('API & Entegrasyonlar')
                                ->icon('heroicon-o-cpu-chip')
                                ->collapsible()
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextEntry::make('api_integrations_enabled')
                                                ->label('API Entegrasyonu')
                                                ->badge()
                                                ->color(fn ($state) => $state ? 'success' : 'gray')
                                                ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Pasif'),

                                            TextEntry::make('webhooks_enabled')
                                                ->label('Webhooks')
                                                ->badge()
                                                ->color(fn ($state) => $state ? 'success' : 'gray')
                                                ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Pasif'),

                                            TextEntry::make('real_time_sync_enabled')
                                                ->label('Gerçek Zamanlı Senkron')
                                                ->badge()
                                                ->color(fn ($state) => $state ? 'success' : 'gray')
                                                ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Pasif'),

                                            TextEntry::make('max_api_calls_per_month')
                                                ->label('Aylık API Limiti')
                                                ->numeric()
                                                ->suffix(' çağrı'),
                                        ]),
                                ]),
                        ])->columnSpan(1),

                        Group::make([
                            Section::make('Mağazalar')
                                ->icon('heroicon-o-building-storefront')
                                ->headerActions([
                                    Action::make('view_all')
                                        ->label('Tümünü Gör')
                                        ->url(fn ($record) => route('filament.admin.resources.stores.index', ['tableFilters[company_id][value]' => $record->id]))
                                        ->color('gray')
                                        ->size('sm'),
                                ])
                                ->schema([
                                    ViewEntry::make('stores')
                                        ->label('')
                                        ->view('filament.infolists.company-stores'),
                                ]),

                            Section::make('Kullanıcılar')
                                ->icon('heroicon-o-users')
                                ->schema([
                                    ViewEntry::make('users')
                                        ->label('')
                                        ->view('filament.infolists.company-users'),
                                ]),

                            Section::make('Son Aktiviteler')
                                ->icon('heroicon-o-clock')
                                ->collapsible()
                                ->schema([
                                    ViewEntry::make('recent_activities')
                                        ->label('')
                                        ->view('filament.infolists.company-activities')
                                        ->viewData(fn ($record) => [
                                            'activities' => $this->getRecentActivities($record),
                                        ]),
                                ]),
                        ])->columnSpan(1),
                    ]),

                Section::make('Mağaza Performansları')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsible()
                    ->schema([
                        ViewEntry::make('stores_performance')
                            ->label('')
                            ->view('filament.infolists.stores-performance')
                            ->viewData(fn ($record) => [
                                'stores' => $record->stores()->with('transactions')->get(),
                                'currency' => $record->currency,
                            ]),
                    ]),

                Section::make('Plan Özellikleri')
                    ->icon('heroicon-o-sparkles')
                    ->collapsible()
                    ->schema([
                        ViewEntry::make('plan_features')
                            ->label('')
                            ->view('filament.infolists.plan-features')
                            ->viewData(fn ($record) => [
                                'plan' => $record->plan,
                                'settings' => $record->settings,
                                'features' => $this->getPlanFeatures($record),
                            ]),
                    ]),
            ]);
    }

    protected function getRecentActivities($record): array
    {
        $activities = [];

        // Son işlemler
        $recentTransactions = $record->transactions()
            ->with(['store', 'createdBy'])
            ->latest()
            ->take(3)
            ->get();

        foreach ($recentTransactions as $transaction) {
            $activities[] = [
                'type' => 'transaction',
                'title' => $transaction->type === 'income' ? 'Gelir Kaydı' : 'Gider Kaydı',
                'description' => $transaction->description ?? $transaction->transaction_id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'store' => $transaction->store->name,
                'user' => $transaction->createdBy?->name ?? 'Sistem',
                'date' => $transaction->created_at,
            ];
        }

        // Son kullanıcılar
        $recentUsers = $record->users()
            ->latest()
            ->take(2)
            ->get();

        foreach ($recentUsers as $user) {
            $activities[] = [
                'type' => 'user',
                'title' => 'Yeni Kullanıcı',
                'description' => $user->name . ' eklendi',
                'store' => null,
                'user' => $user->name,
                'date' => $user->created_at,
            ];
        }

        // Tarihe göre sırala
        usort($activities, function ($a, $b) {
            return $b['date']->timestamp - $a['date']->timestamp;
        });

        return array_slice($activities, 0, 5);
    }

    protected function getPlanFeatures($record): array
    {
        $features = [
            'free' => [
                'stores' => 1,
                'users' => 2,
                'transactions' => 100,
                'api' => false,
                'webhooks' => false,
                'support' => 'Email',
            ],
            'starter' => [
                'stores' => 3,
                'users' => 5,
                'transactions' => 1000,
                'api' => true,
                'webhooks' => false,
                'support' => 'Email + Chat',
            ],
            'professional' => [
                'stores' => 10,
                'users' => 20,
                'transactions' => 10000,
                'api' => true,
                'webhooks' => true,
                'support' => 'Öncelikli',
            ],
            'enterprise' => [
                'stores' => 'Sınırsız',
                'users' => 'Sınırsız',
                'transactions' => 'Sınırsız',
                'api' => true,
                'webhooks' => true,
                'support' => '7/24 Özel',
            ],
        ];

        return $features[$record->plan] ?? $features['free'];
    }
}