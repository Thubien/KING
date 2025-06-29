<?php

namespace App\Filament\Pages;

use App\Models\ImportBatch;
use App\Models\Store;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class TransactionHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Transactions';
    protected static ?string $title = 'Transaction Management Hub';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Financial';
    
    protected static string $view = 'filament.pages.transaction-hub';

    // Stats properties
    public int $totalTransactions = 0;
    public int $pendingTransactions = 0;
    public int $todayTransactions = 0;
    public ?string $lastImportTime = 'Never';
    public array $recentImports = [];
    public array $storeBalances = [];
    public array $pendingByCategory = [
        'likely_sales' => 0,
        'likely_ads' => 0,
        'likely_fees' => 0,
        'other' => 0
    ];
    
    public function mount(): void
    {
        try {
            $this->loadStats();
        } catch (\Exception $e) {
            \Log::error('TransactionHub mount error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    #[On('stats-updated')]
    public function loadStats(): void
    {
        $companyId = Auth::user()->company_id;
        
        \Log::info('Loading TransactionHub stats for company: ' . $companyId);
        
        // Basic stats
        $baseQuery = Transaction::whereHas('store', fn($q) => $q->where('company_id', $companyId));
        $this->totalTransactions = $baseQuery->count();
        $this->pendingTransactions = $baseQuery->clone()->where('assignment_status', 'pending')->count();
        $this->todayTransactions = $baseQuery->clone()->whereDate('created_at', today())->count();
        
        // Last import
        $lastImport = ImportBatch::where('initiator_id', Auth::id())
            ->latest()
            ->first();
        $this->lastImportTime = $lastImport?->created_at?->diffForHumans() ?? 'Never';
        
        // Recent imports (last 5)
        $this->recentImports = ImportBatch::where('initiator_id', Auth::id())
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($batch) => [
                'id' => $batch->id,
                'source' => $batch->source_type ?? 'Unknown',
                'records' => $batch->total_records,
                'status' => $batch->status,
                'time' => $batch->created_at->diffForHumans(),
            ])
            ->toArray();
            
        // Store balances with pending counts
        $this->storeBalances = Store::where('company_id', $companyId)
            ->withCount(['transactions as pending_count' => function($q) {
                $q->where('assignment_status', 'pending');
            }])
            ->get()
            ->map(fn($store) => [
                'id' => $store->id,
                'name' => $store->name,
                'currency' => $store->currency,
                'pending' => $store->pending_count,
                'balance' => $store->calculated_balance ?? 0,
            ])
            ->toArray();
            
        // Pending by potential category (smart grouping)
        $this->pendingByCategory = [
            'likely_sales' => Transaction::where('assignment_status', 'pending')
                ->where('amount', '>', 0)
                ->whereHas('store', fn($q) => $q->where('company_id', $companyId))
                ->count(),
            'likely_ads' => Transaction::where('assignment_status', 'pending')
                ->where('amount', '<', 0)
                ->where(function($q) {
                    $q->where('description', 'like', '%facebook%')
                      ->orWhere('description', 'like', '%google%')
                      ->orWhere('description', 'like', '%meta%');
                })
                ->whereHas('store', fn($q) => $q->where('company_id', $companyId))
                ->count(),
            'likely_fees' => Transaction::where('assignment_status', 'pending')
                ->where('amount', '<', 0)
                ->where(function($q) {
                    $q->where('description', 'like', '%fee%')
                      ->orWhere('description', 'like', '%charge%')
                      ->orWhere('description', 'like', '%commission%');
                })
                ->whereHas('store', fn($q) => $q->where('company_id', $companyId))
                ->count(),
            'other' => Transaction::where('assignment_status', 'pending')
                ->whereHas('store', fn($q) => $q->where('company_id', $companyId))
                ->count() - ($this->pendingByCategory['likely_sales'] ?? 0) - ($this->pendingByCategory['likely_ads'] ?? 0) - ($this->pendingByCategory['likely_fees'] ?? 0),
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->url('/admin/import-transactions')
                ->size('lg'),
                
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('loadStats'),
        ];
    }
    
    public function navigateToEditor(?string $filter = null): void
    {
        $url = route('filament.admin.pages.transaction-editor');
        if ($filter) {
            $url .= '?filter=' . $filter;
        }
        $this->redirect($url);
    }
    
    public function navigateToImportHistory(): void
    {
        $this->redirect(route('filament.admin.resources.import-batches.index'));
    }
}