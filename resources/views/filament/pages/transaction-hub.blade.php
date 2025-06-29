<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Hero Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Transactions --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transactions</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalTransactions) }}</p>
                        </div>
                        <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <x-heroicon-o-document-text class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Pending Assignment --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm relative">
                @if($pendingTransactions > 0)
                    <div class="absolute top-0 right-0 w-1 h-full bg-warning-500 rounded-r-xl"></div>
                @endif
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Assignment</p>
                            <p class="text-2xl font-bold {{ $pendingTransactions > 0 ? 'text-warning-600' : 'text-gray-900' }} dark:text-gray-100">
                                {{ number_format($pendingTransactions) }}
                            </p>
                        </div>
                        <div class="p-3 {{ $pendingTransactions > 0 ? 'bg-warning-100 dark:bg-warning-900/20' : 'bg-gray-100 dark:bg-gray-700' }} rounded-xl">
                            <x-heroicon-o-clock class="w-6 h-6 {{ $pendingTransactions > 0 ? 'text-warning-600' : 'text-gray-600' }} dark:text-gray-400" />
                        </div>
                    </div>
                    @if($pendingTransactions > 0)
                        <button wire:click="navigateToEditor" class="mt-4 text-xs text-warning-600 hover:text-warning-700 font-medium">
                            Assign now →
                        </button>
                    @endif
                </div>
            </div>
            
            {{-- Today's Activity --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Today's Activity</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($todayTransactions) }}</p>
                        </div>
                        <div class="p-3 bg-success-100 dark:bg-success-900/20 rounded-xl">
                            <x-heroicon-o-calendar class="w-6 h-6 text-success-600 dark:text-success-400" />
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Last Import --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Import</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $lastImportTime }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-xl">
                            <x-heroicon-o-arrow-up-tray class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Action Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Import New Transactions --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <a href="{{ route('filament.admin.pages.import-transactions') }}" class="block">
                    <div class="flex items-center gap-x-2">
                        <span class="fi-wi-stats-overview-stat-icon fi-color-custom text-custom-500" style="--c-500: var(--primary-500)">
                            <x-heroicon-o-arrow-up-tray class="h-6 w-6" />
                        </span>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Import Transactions
                        </span>
                    </div>
                    <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-2">
                        CSV
                    </div>
                    <div class="fi-wi-stats-overview-stat-description text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Upload bank statements
                    </div>
                </a>
            </div>
            
            {{-- Process Pending --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 cursor-pointer" wire:click="navigateToEditor">
                <div class="flex items-center gap-x-2">
                    <span class="fi-wi-stats-overview-stat-icon fi-color-custom text-custom-500" style="--c-500: var(--warning-500)">
                        <x-heroicon-o-pencil-square class="h-6 w-6" />
                    </span>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Process Pending
                    </span>
                </div>
                <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-2">
                    {{ $pendingTransactions }}
                </div>
                <div class="fi-wi-stats-overview-stat-description text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Assign stores and categories
                </div>
            </div>
            
            {{-- View History --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 cursor-pointer" wire:click="navigateToImportHistory">
                <div class="flex items-center gap-x-2">
                    <span class="fi-wi-stats-overview-stat-icon fi-color-custom text-custom-500" style="--c-500: var(--gray-500)">
                        <x-heroicon-o-clock class="h-6 w-6" />
                    </span>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Import History
                    </span>
                </div>
                <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-2">
                    {{ count($recentImports) }}
                </div>
                <div class="fi-wi-stats-overview-stat-description text-sm text-gray-500 dark:text-gray-400 mt-1">
                    View past imports
                </div>
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <a href="{{ route('filament.admin.pages.return-kanban') }}" class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 hover:shadow-md transition-all">
                <div class="flex items-center gap-x-2">
                    <span class="fi-wi-stats-overview-stat-icon text-purple-500">
                        <x-heroicon-o-arrow-uturn-left class="h-5 w-5" />
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        İade Takip
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Kanban board
                </p>
            </a>
            
            <a href="{{ route('filament.admin.resources.inventory-items.index') }}" class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 hover:shadow-md transition-all">
                <div class="flex items-center gap-x-2">
                    <span class="fi-wi-stats-overview-stat-icon text-primary-500">
                        <x-heroicon-o-cube class="h-5 w-5" />
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Inventory Management
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Manage stock items
                </p>
            </a>
            
            <a href="{{ route('filament.admin.pages.balance-validation') }}" class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 hover:shadow-md transition-all">
                <div class="flex items-center gap-x-2">
                    <span class="fi-wi-stats-overview-stat-icon text-success-500">
                        <x-heroicon-o-calculator class="h-5 w-5" />
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Balance Validation
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Check financial health
                </p>
            </a>
            
            <a href="{{ route('filament.admin.resources.settlements.index') }}" class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 hover:shadow-md transition-all">
                <div class="flex items-center gap-x-2">
                    <span class="fi-wi-stats-overview-stat-icon text-warning-500">
                        <x-heroicon-o-banknotes class="h-5 w-5" />
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Debt Settlements
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Partner debt management
                </p>
            </a>
            
            <a href="{{ route('filament.admin.resources.partnerships.index') }}" class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 hover:shadow-md transition-all">
                <div class="flex items-center gap-x-2">
                    <span class="fi-wi-stats-overview-stat-icon text-info-500">
                        <x-heroicon-o-user-group class="h-5 w-5" />
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Partnerships
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Manage store partners
                </p>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Store Overview --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Store Overview</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pending transactions by store</p>
                </div>
                <div class="p-6">
                    @if(count($storeBalances) > 0)
                        <div class="space-y-4">
                            @foreach($storeBalances as $store)
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-primary-100 dark:bg-primary-900/20 rounded-lg">
                                            <x-heroicon-o-building-storefront class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $store['name'] }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $store['currency'] }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        @if($store['pending'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-800 dark:bg-warning-900/20 dark:text-warning-200">
                                                {{ $store['pending'] }} pending
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-500 dark:text-gray-400">All assigned</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No stores found</p>
                    @endif
                </div>
            </div>
            
            {{-- Smart Insights --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Smart Insights</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">AI-detected transaction patterns</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        {{-- Likely Sales --}}
                        @if($pendingByCategory['likely_sales'] > 0)
                            <div class="flex items-center justify-between p-4 bg-success-50 dark:bg-success-900/10 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-success-100 dark:bg-success-900/20 rounded-lg">
                                        <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-success-600 dark:text-success-400" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">Likely Sales</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Income transactions</p>
                                    </div>
                                </div>
                                <button wire:click="navigateToEditor('income')" class="text-sm font-medium text-success-600 hover:text-success-700">
                                    {{ $pendingByCategory['likely_sales'] }} →
                                </button>
                            </div>
                        @endif
                        
                        {{-- Likely Ads --}}
                        @if($pendingByCategory['likely_ads'] > 0)
                            <div class="flex items-center justify-between p-4 bg-purple-50 dark:bg-purple-900/10 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
                                        <x-heroicon-o-megaphone class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">Likely Advertising</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Facebook, Google ads</p>
                                    </div>
                                </div>
                                <button wire:click="navigateToEditor('ads')" class="text-sm font-medium text-purple-600 hover:text-purple-700">
                                    {{ $pendingByCategory['likely_ads'] }} →
                                </button>
                            </div>
                        @endif
                        
                        {{-- Likely Fees --}}
                        @if($pendingByCategory['likely_fees'] > 0)
                            <div class="flex items-center justify-between p-4 bg-orange-50 dark:bg-orange-900/10 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-orange-100 dark:bg-orange-900/20 rounded-lg">
                                        <x-heroicon-o-receipt-percent class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">Likely Fees</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Bank & processor fees</p>
                                    </div>
                                </div>
                                <button wire:click="navigateToEditor('fees')" class="text-sm font-medium text-orange-600 hover:text-orange-700">
                                    {{ $pendingByCategory['likely_fees'] }} →
                                </button>
                            </div>
                        @endif
                        
                        {{-- Other --}}
                        @if($pendingByCategory['other'] > 0)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                        <x-heroicon-o-question-mark-circle class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">Other Transactions</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Needs manual review</p>
                                    </div>
                                </div>
                                <button wire:click="navigateToEditor" class="text-sm font-medium text-gray-600 hover:text-gray-700">
                                    {{ $pendingByCategory['other'] }} →
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    @if($pendingTransactions == 0)
                        <div class="flex items-center justify-center p-4 bg-success-50 dark:bg-success-900/10 rounded-lg">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-success-600 dark:text-success-400 mr-2" />
                            <p class="text-sm font-medium text-success-700 dark:text-success-300">All transactions assigned!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Recent Import Activity --}}
        @if(count($recentImports) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Imports</h3>
                        <button wire:click="navigateToImportHistory" class="text-sm text-primary-600 hover:text-primary-700">
                            View all →
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Records</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($recentImports as $import)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ ucfirst($import['source']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $import['records'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $import['status'] === 'completed' ? 'bg-success-100 text-success-800 dark:bg-success-900/20 dark:text-success-200' : '' }}
                                            {{ $import['status'] === 'processing' ? 'bg-warning-100 text-warning-800 dark:bg-warning-900/20 dark:text-warning-200' : '' }}
                                            {{ $import['status'] === 'failed' ? 'bg-danger-100 text-danger-800 dark:bg-danger-900/20 dark:text-danger-200' : '' }}
                                        ">
                                            {{ ucfirst($import['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $import['time'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
    
    @push('scripts')
    <script>
        // Auto-refresh stats every 30 seconds
        setInterval(() => {
            @this.loadStats();
        }, 30000);
    </script>
    @endpush
</x-filament-panels::page>