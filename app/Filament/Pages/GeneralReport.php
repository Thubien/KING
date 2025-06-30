<?php

namespace App\Filament\Pages;

use App\Models\Store;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeneralReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'General Report';
    protected static ?string $title = 'General Report';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.general-report';

    // Filtreler
    public ?int $store_id = null;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $selected_preset = 'last_30_days';

    // Data
    public Collection $stores;
    public Collection $dailyData;
    public array $totals = [];
    public array $stats = [];
    public ?Store $selectedStore = null;
    public int $dayCount = 0;

    public function mount(): void
    {
        // Varsayılan tarih aralığı - son 30 gün
        $this->end_date = now()->format('Y-m-d');
        $this->start_date = now()->subDays(29)->format('Y-m-d');
        
        // Kullanıcının erişebileceği mağazaları yükle
        $this->loadStores();
        
        // İlk yüklemede veri getir
        $this->refreshReport();
    }

    protected function loadStores(): void
    {
        $user = auth()->user();
        
        if ($user->isAdmin() || $user->isCompanyOwner()) {
            $this->stores = Store::where('company_id', $user->company_id)
                ->where('status', 'active')
                ->get();
        } else {
            $storeIds = $user->getAccessibleStoreIds();
            $this->stores = Store::whereIn('id', $storeIds)
                ->where('status', 'active')
                ->get();
        }
    }

    public function selectStore(?int $storeId = null): void
    {
        $this->store_id = $storeId;
        $this->selectedStore = $storeId ? Store::find($storeId) : null;
        $this->refreshReport();
    }

    public function setDatePreset(string $preset): void
    {
        $this->selected_preset = $preset;
        $now = now();
        
        switch ($preset) {
            case 'today':
                $this->start_date = $now->format('Y-m-d');
                $this->end_date = $now->format('Y-m-d');
                break;
            case 'yesterday':
                $this->start_date = $now->subDay()->format('Y-m-d');
                $this->end_date = $now->format('Y-m-d');
                break;
            case 'last_7_days':
                $this->start_date = $now->subDays(6)->format('Y-m-d');
                $this->end_date = now()->format('Y-m-d');
                break;
            case 'last_30_days':
                $this->start_date = $now->subDays(29)->format('Y-m-d');
                $this->end_date = now()->format('Y-m-d');
                break;
            case 'last_month':
                $this->start_date = $now->subMonth()->startOfMonth()->format('Y-m-d');
                $this->end_date = $now->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_3_months':
                $this->start_date = $now->subMonths(3)->format('Y-m-d');
                $this->end_date = now()->format('Y-m-d');
                break;
            case 'last_6_months':
                $this->start_date = $now->subMonths(6)->format('Y-m-d');
                $this->end_date = now()->format('Y-m-d');
                break;
            case 'this_year':
                $this->start_date = $now->startOfYear()->format('Y-m-d');
                $this->end_date = now()->format('Y-m-d');
                break;
            case 'max':
                $this->start_date = now()->subYears(2)->format('Y-m-d');
                $this->end_date = now()->format('Y-m-d');
                break;
        }
        
        $this->refreshReport();
    }

    public function updateDates(): void
    {
        $this->selected_preset = 'custom';
        $this->refreshReport();
    }

    public function refreshReport(): void
    {
        // Gün sayısını hesapla
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        $this->dayCount = $startDate->diffInDays($endDate) + 1;

        // Ana query
        $query = Transaction::query()
            ->whereBetween('transaction_date', [$this->start_date, $this->end_date])
            ->where('status', 'completed');

        // Mağaza filtresi
        if ($this->store_id) {
            $query->where('store_id', $this->store_id);
        } else {
            // Tüm mağazalar seçiliyse, kullanıcının erişebileceği mağazaları filtrele
            $accessibleStoreIds = auth()->user()->getAccessibleStoreIds();
            $query->whereIn('store_id', $accessibleStoreIds);
        }

        // Günlük verileri topla
        $rawData = $query
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                'category',
                DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE -amount END) as total_amount'),
                DB::raw('SUM(CASE WHEN type = "income" THEN amount_usd ELSE -amount_usd END) as total_amount_usd')
            )
            ->groupBy('date', 'category')
            ->orderBy('date')
            ->get();

        // Verileri işle
        $this->processDailyData($rawData);
        $this->calculateStats();
    }

    protected function processDailyData($rawData): void
    {
        // Tarih aralığındaki tüm günleri oluştur
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        $dates = [];
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }

        // Boş günlük veri yapısı
        $emptyDay = [
            'sales' => 0,
            'returns' => 0,
            'pay_product' => 0,
            'pay_delivery' => 0,
            'withdraw' => 0,
            'bank_fee' => 0,
            'fee' => 0,
            'ads' => 0,
            'other_pay' => 0,
            'net_profit' => 0,
        ];

        // Günlük verileri organize et
        $dailyData = collect($dates)->mapWithKeys(function ($date) use ($emptyDay) {
            return [$date => $emptyDay];
        });

        // Gerçek verileri yerleştir
        foreach ($rawData as $item) {
            $date = $item->date;
            $category = strtolower(str_replace('-', '_', $item->category));
            
            // Handle BANK_FEE vs BANK_COM mapping
            if ($category === 'bank_com') {
                $category = 'bank_fee';
            }
            
            $amount = $this->store_id ? $item->total_amount : $item->total_amount_usd;

            if (isset($dailyData[$date][$category])) {
                $dailyData[$date][$category] = abs($amount);
            }
        }

        // Net kar hesapla ve Collection'a dönüştür
        $this->dailyData = $dailyData->map(function ($day, $date) {
            $income = $day['sales'];
            $expenses = $day['returns'] + $day['pay_product'] + $day['pay_delivery'] + 
                       $day['withdraw'] + $day['bank_fee'] + $day['fee'] + 
                       $day['ads'] + $day['other_pay'];
            
            $day['net_profit'] = $income - $expenses;
            $day['date'] = $date;
            
            return (object) $day;
        })->values();

        // Toplamları hesapla
        $this->totals = [
            'sales' => $this->dailyData->sum('sales'),
            'returns' => $this->dailyData->sum('returns'),
            'pay_product' => $this->dailyData->sum('pay_product'),
            'pay_delivery' => $this->dailyData->sum('pay_delivery'),
            'withdraw' => $this->dailyData->sum('withdraw'),
            'bank_fee' => $this->dailyData->sum('bank_fee'),
            'fee' => $this->dailyData->sum('fee'),
            'ads' => $this->dailyData->sum('ads'),
            'other_pay' => $this->dailyData->sum('other_pay'),
            'net_profit' => $this->dailyData->sum('net_profit'),
        ];
    }

    protected function calculateStats(): void
    {
        $this->stats = [
            'total_income' => $this->totals['sales'],
            'total_expense' => $this->totals['returns'] + $this->totals['pay_product'] + 
                              $this->totals['pay_delivery'] + $this->totals['withdraw'] + 
                              $this->totals['bank_fee'] + $this->totals['fee'] + 
                              $this->totals['ads'] + $this->totals['other_pay'],
            'net_profit' => $this->totals['net_profit'],
        ];
    }

    public function getCurrency(): string
    {
        if ($this->store_id && $this->selectedStore) {
            return $this->selectedStore->currency;
        }
        return 'USD'; // Tüm mağazalar için USD
    }

    public function viewCategoryDetails(string $category): void
    {
        // Map BANK_FEE to BANK_COM for transaction filtering
        $transactionCategory = $category === 'BANK_FEE' ? 'BANK_COM' : $category;
        
        // Session'a filtre bilgilerini kaydet
        session([
            'general_report_filters' => [
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'store_id' => $this->store_id,
                'category' => $transactionCategory,
            ]
        ]);

        // Transaction listesine yönlendir (filtreli)
        redirect()->route('filament.admin.resources.transactions.index', [
            'tableFilters' => [
                'category' => ['value' => $transactionCategory],
                'date_range' => [
                    'transaction_date' => [
                        'from' => $this->start_date,
                        'until' => $this->end_date,
                    ]
                ],
                'store' => ['value' => $this->store_id],
            ]
        ]);
    }
    
    public function exportReport(): void
    {
        // TODO: Implement Excel export functionality
        \Filament\Notifications\Notification::make()
            ->title('Excel İndir')
            ->body('Excel export özelliği yakında eklenecek.')
            ->info()
            ->send();
    }
    
    public function printReport(): void
    {
        // TODO: Implement print functionality
        $this->dispatch('print-report');
    }
}