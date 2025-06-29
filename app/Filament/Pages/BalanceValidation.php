<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\BalanceValidationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class BalanceValidation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Balance Validation';
    protected static ?string $title = 'Real-time Balance Validation';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationGroup = 'Financial';
    
    protected static string $view = 'filament.pages.balance-validation';
    
    public $validationResult = null;
    public $isLoading = false;
    public $lastValidated = null;
    public $autoRefresh = true;
    
    public static function canAccess(): bool
    {
        return Auth::user()->isCompanyOwner() || Auth::user()->isAdmin();
    }
    
    public function mount(): void
    {
        $this->loadValidationResult();
    }
    
    #[On('refresh-validation')]
    public function loadValidationResult(): void
    {
        $this->isLoading = true;
        
        $company = Company::find(Auth::user()->company_id);
        if (!$company) {
            return;
        }
        
        $balanceService = new BalanceValidationService();
        $this->validationResult = $balanceService->getCachedBalance($company);
        $this->lastValidated = Cache::get("balance_validated_at_{$company->id}", now());
        
        $this->isLoading = false;
    }
    
    public function forceValidation(): void
    {
        $this->isLoading = true;
        
        $company = Company::find(Auth::user()->company_id);
        if (!$company) {
            return;
        }
        
        $balanceService = new BalanceValidationService();
        $this->validationResult = $balanceService->forceRecalculation($company);
        
        Cache::put("balance_validated_at_{$company->id}", now(), 3600);
        $this->lastValidated = now();
        
        $this->isLoading = false;
        
        Notification::make()
            ->title('Balance validation completed')
            ->body($this->validationResult['is_valid'] 
                ? 'All balances are valid!' 
                : 'Balance discrepancy detected: $' . number_format($this->validationResult['difference'], 2))
            ->icon($this->validationResult['is_valid'] ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle')
            ->iconColor($this->validationResult['is_valid'] ? 'success' : 'danger')
            ->send();
    }
    
    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('validate')
                ->label('Validate Now')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action('forceValidation')
                ->disabled(fn () => $this->isLoading),
                
            Action::make('autoRefresh')
                ->label($this->autoRefresh ? 'Auto-refresh ON' : 'Auto-refresh OFF')
                ->icon($this->autoRefresh ? 'heroicon-o-play' : 'heroicon-o-pause')
                ->color($this->autoRefresh ? 'success' : 'gray')
                ->action('toggleAutoRefresh'),
                
            Action::make('export')
                ->label('Export Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    // Export logic here
                    Notification::make()
                        ->title('Report exported')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    public function getViewData(): array
    {
        return [
            'validationResult' => $this->validationResult,
            'isLoading' => $this->isLoading,
            'lastValidated' => $this->lastValidated,
            'autoRefresh' => $this->autoRefresh,
        ];
    }
}