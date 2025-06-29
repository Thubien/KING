<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Loading State --}}
        @if($isLoading)
            <div class="flex items-center justify-center py-12">
                <div class="text-center">
                    <div class="inline-flex items-center space-x-3">
                        <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-400">Validating balances...</span>
                    </div>
                </div>
            </div>
        @endif
        
        @if($validationResult && !$isLoading)
            {{-- Status Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 {{ $validationResult['is_valid'] ? 'bg-success-50 dark:bg-success-900/20' : 'bg-danger-50 dark:bg-danger-900/20' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            @if($validationResult['is_valid'])
                                <x-heroicon-o-check-circle class="w-10 h-10 text-success-600" />
                            @else
                                <x-heroicon-o-exclamation-triangle class="w-10 h-10 text-danger-600" />
                            @endif
                            <div>
                                <h2 class="text-2xl font-bold {{ $validationResult['is_valid'] ? 'text-success-700' : 'text-danger-700' }}">
                                    {{ $validationResult['is_valid'] ? 'Balances Valid' : 'Balance Discrepancy Detected' }}
                                </h2>
                                <p class="text-sm {{ $validationResult['is_valid'] ? 'text-success-600' : 'text-danger-600' }} mt-1">
                                    @if($validationResult['is_valid'])
                                        All financial balances match within tolerance
                                    @else
                                        Difference: ${{ number_format($validationResult['difference'], 2) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Last validated</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $lastValidated ? $lastValidated->diffForHumans() : 'Never' }}
                            </p>
                        </div>
                    </div>
                </div>
                
                {{-- Summary Cards --}}
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Real Money Total</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                            ${{ number_format($validationResult['real_money_total'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Bank + Processors</p>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Calculated Balance</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                            ${{ number_format($validationResult['calculated_balance'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">From Transactions</p>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Difference</p>
                        <p class="text-2xl font-bold {{ $validationResult['is_valid'] ? 'text-success-600' : 'text-danger-600' }} mt-1">
                            ${{ number_format($validationResult['difference'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tolerance: ${{ number_format($validationResult['tolerance'], 2) }}</p>
                    </div>
                </div>
            </div>
            
            {{-- Detailed Breakdown --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Bank & Processor Accounts --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Real Money Accounts</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        {{-- Bank Accounts --}}
                        @if(count($validationResult['breakdown']['bank_accounts']) > 0)
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Bank Accounts</h4>
                                <div class="space-y-2">
                                    @foreach($validationResult['breakdown']['bank_accounts'] as $account)
                                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $account['bank_type'] }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $account['currency'] }}</p>
                                            </div>
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $account['formatted_balance'] }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        {{-- Payment Processors --}}
                        @if(count($validationResult['breakdown']['payment_processors']) > 0)
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Payment Processors</h4>
                                <div class="space-y-2">
                                    @foreach($validationResult['breakdown']['payment_processors'] as $processor)
                                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                            <div class="flex justify-between items-center mb-2">
                                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $processor['processor_type'] }}</p>
                                                <p class="font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $processor['formatted_total'] }}
                                                </p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 text-xs">
                                                <div>
                                                    <span class="text-gray-500 dark:text-gray-400">Current:</span>
                                                    <span class="text-gray-700 dark:text-gray-300 ml-1">{{ $processor['formatted_current'] }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500 dark:text-gray-400">Pending:</span>
                                                    <span class="text-gray-700 dark:text-gray-300 ml-1">{{ $processor['formatted_pending'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Store Balances --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Store Calculated Balances</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-2">
                            @foreach($validationResult['breakdown']['stores'] as $store)
                                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $store['name'] }}</p>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $store['formatted_balance'] }}
                                        </p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Income transactions:</span>
                                            <span class="text-gray-700 dark:text-gray-300 ml-1">{{ $store['transaction_counts']['income'] }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Expense transactions:</span>
                                            <span class="text-gray-700 dark:text-gray-300 ml-1">{{ $store['transaction_counts']['expenses'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Actions for Discrepancies --}}
            @if(!$validationResult['is_valid'])
                <div class="bg-warning-50 dark:bg-warning-900/20 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-warning-800 dark:text-warning-200 mb-4">Recommended Actions</h3>
                    <ul class="space-y-2 text-sm text-warning-700 dark:text-warning-300">
                        <li class="flex items-start">
                            <x-heroicon-o-chevron-right class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                            <span>Review recent transactions for any missing or duplicate entries</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-o-chevron-right class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                            <span>Check payment processor pending balances for accuracy</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-o-chevron-right class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                            <span>Verify bank account balances match actual statements</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-o-chevron-right class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                            <span>Contact support if the discrepancy persists</span>
                        </li>
                    </ul>
                </div>
            @endif
        @endif
    </div>
    
    @if($autoRefresh)
        @push('scripts')
        <script>
            // Auto-refresh every 30 seconds
            setInterval(() => {
                @this.dispatch('refresh-validation');
            }, 30000);
        </script>
        @endpush
    @endif
</x-filament-panels::page>