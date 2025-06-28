<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Section --}}
        <div class="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 border border-green-200 dark:border-green-800 rounded-xl p-6">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <x-heroicon-o-building-storefront class="w-8 h-8 text-green-600 dark:text-green-400" />
                    </div>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        🛒 Shopify Store Management
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Connect your Shopify stores to automatically sync transactions, manage partnerships, and track performance across all your e-commerce channels.
                    </p>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('filament.admin.resources.stores.index') }}" 
               class="block p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div class="flex items-center space-x-3">
                    <x-heroicon-o-eye class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-gray-100">View All Stores</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Manage store settings</p>
                    </div>
                </div>
            </a>
            
            <a href="{{ route('filament.admin.resources.partnerships.index') }}" 
               class="block p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div class="flex items-center space-x-3">
                    <x-heroicon-o-user-group class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-gray-100">Partnerships</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Setup profit sharing</p>
                    </div>
                </div>
            </a>
            
            <a href="{{ route('filament.admin.resources.transactions.index') }}" 
               class="block p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div class="flex items-center space-x-3">
                    <x-heroicon-o-banknotes class="w-6 h-6 text-green-600 dark:text-green-400" />
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-gray-100">Transactions</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">View synced orders</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Integration Guide --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                🚀 Getting Started with Shopify
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <span class="text-xs font-bold text-blue-600 dark:text-blue-400">1</span>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">Connect Your Store</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Use the connection form above to link your Shopify store</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <span class="text-xs font-bold text-blue-600 dark:text-blue-400">2</span>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">Setup Partnerships</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Define profit sharing with partners and sales reps</p>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <span class="text-xs font-bold text-blue-600 dark:text-blue-400">3</span>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">Automatic Sync</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Orders sync automatically every hour</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <span class="text-xs font-bold text-blue-600 dark:text-blue-400">4</span>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">Track Performance</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Monitor sales, commissions, and revenue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>