<x-filament-panels::page>
    <div class="space-y-8">
        {{-- Analytics Header --}}
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-chart-bar class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            📊 Business Analytics & Performance
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            Comprehensive insights into your multi-store e-commerce empire. Track revenue, partnerships, and sales rep performance across all channels.
                        </p>
                    </div>
                </div>
                
                <div class="hidden md:block">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ now()->format('M Y') }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Current Period
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Insights --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-currency-dollar class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Revenue Growth</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Month over month</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-building-storefront class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Store Performance</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Cross-platform analytics</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-user-group class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Team Performance</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Sales rep rankings</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-chart-pie class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Channel Mix</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Sales distribution</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Key Metrics Cards --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    🎯 Revenue Targets
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Monthly Goal</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">$50,000</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: 78%"></div>
                    </div>
                    <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                        <span>78% complete</span>
                        <span>12 days left</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    🏆 Top Channel
                </h3>
                <div class="text-center">
                    <div class="text-3xl mb-2">🛒</div>
                    <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">Shopify</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">67% of total sales</div>
                    <div class="text-xs text-green-600 dark:text-green-400 mt-1">+15% vs last month</div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    💼 Active Partnerships
                </h3>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Partners</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ Auth::user()->company->partnerships()->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Sales Reps</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ Auth::user()->company->users()->whereHas('roles', fn($q) => $q->where('name', 'sales_rep'))->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Active Stores</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ Auth::user()->company->stores()->where('status', 'active')->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Export & Actions --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                        📋 Export & Reports
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Generate detailed reports for accounting, tax preparation, and business analysis.
                    </p>
                </div>
                
                <div class="flex space-x-3">
                    <button class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <x-heroicon-o-document-arrow-down class="w-4 h-4 mr-2" />
                        Export CSV
                    </button>
                    <button class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <x-heroicon-o-chart-bar class="w-4 h-4 mr-2" />
                        Custom Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>