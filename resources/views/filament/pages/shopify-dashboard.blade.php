<x-filament-panels::page>
    @push('styles')
    <style>
        /* Sadece bu sayfa için özel stiller */
        .custom-card {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .custom-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .stat-card {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: all 0.3s ease;
            display: block;
            text-decoration: none;
        }
        
        .stat-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .stat-card:hover.hover\:border-blue-400 {
            border-color: #60a5fa;
        }
        
        .stat-card:hover.hover\:border-purple-400 {
            border-color: #c084fc;
        }
        
        .stat-card:hover.hover\:border-green-400 {
            border-color: #4ade80;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            border-radius: 0.75rem;
            padding: 1.5rem;
            color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .step-number {
            width: 2rem;
            height: 2rem;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .gradient-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .gradient-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .icon-box {
            width: 3rem;
            height: 3rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .icon-box-green {
            background-color: #d1fae5;
        }
        
        .icon-box-green svg {
            color: #059669;
        }
        
        .icon-box-blue {
            background-color: #dbeafe;
        }
        
        .icon-box-blue svg {
            color: #2563eb;
        }
        
        .custom-button {
            display: inline-flex;
            align-items: center;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
            border: 2px solid #3b82f6;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);
            min-height: 60px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .custom-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 25px rgba(59, 130, 246, 0.4);
        }
        
        .custom-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .custom-badge-green {
            background-color: #d1fae5;
            color: #065f46;
        }
    </style>
    @endpush
    
    <div class="space-y-6">
        <!-- Welcome Section -->
        <div class="welcome-banner">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-building-storefront', 'w-8 h-8 text-white')
                    </div>
                </div>
                <div>
                    <h2 class="text-xl font-semibold">
                        Shopify Store Management
                    </h2>
                    <p class="text-green-50 mt-1">
                        Connect your Shopify stores to automatically sync transactions, manage partnerships, and track performance across all your e-commerce channels.
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('filament.admin.resources.stores.index') }}" 
               class="stat-card hover:border-blue-400">
                <div class="flex items-center space-x-3">
                    <div class="icon-box icon-box-blue">
                        @svg('heroicon-o-eye', 'w-6 h-6')
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900">View All Stores</h3>
                        <p class="text-sm text-gray-500">Manage store settings</p>
                    </div>
                </div>
            </a>
            
            <a href="{{ route('filament.admin.resources.partnerships.index') }}" 
               class="stat-card hover:border-purple-400">
                <div class="flex items-center space-x-3">
                    <div class="bg-purple-100 p-3 rounded-lg">
                        @svg('heroicon-o-user-group', 'w-6 h-6 text-purple-600')
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900">Partnerships</h3>
                        <p class="text-sm text-gray-500">Setup profit sharing</p>
                    </div>
                </div>
            </a>
            
            <a href="{{ route('filament.admin.resources.transactions.index') }}" 
               class="stat-card hover:border-green-400">
                <div class="flex items-center space-x-3">
                    <div class="icon-box icon-box-green">
                        @svg('heroicon-o-banknotes', 'w-6 h-6')
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900">Transactions</h3>
                        <p class="text-sm text-gray-500">View synced orders</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Integration Guide -->
        <div class="custom-card">
            <h3 class="text-lg font-semibold text-gray-900">Getting Started with Shopify</h3>
            <p class="text-sm text-gray-600 mt-1">Follow these simple steps to connect your Shopify store</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="step-number">1</div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">Connect Your Store</h4>
                            <p class="text-sm text-gray-600">Use the connection form above to link your Shopify store</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="step-number">2</div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">Setup Partnerships</h4>
                            <p class="text-sm text-gray-600">Define profit sharing with partners and sales reps</p>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="step-number">3</div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">Automatic Sync</h4>
                            <p class="text-sm text-gray-600">Orders sync automatically every hour</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="step-number">4</div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">Track Performance</h4>
                            <p class="text-sm text-gray-600">Monitor sales, commissions, and revenue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection Widget -->
        @if(!auth()->user()->company->stores()->where('platform', 'shopify')->exists())
            <div class="gradient-card">
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        @svg('heroicon-o-plus', 'w-10 h-10 text-green-600')
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Shopify Stores Connected</h3>
                    <p class="text-gray-600 mb-6">Connect your first Shopify store to start syncing transactions automatically.</p>
                    <button class="custom-button" onclick="alert('Connect Shopify Store modal would open here')">
                        Connect Shopify Store
                    </button>
                </div>
            </div>
        @else
            <!-- Connected Stores Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @php
                    $shopifyStores = auth()->user()->company->stores()->where('platform', 'shopify')->get();
                    $totalRevenue = $shopifyStores->sum(function($store) {
                        return $store->transactions()->where('category', 'SALES')->sum('amount');
                    });
                    $totalOrders = $shopifyStores->sum(function($store) {
                        return $store->transactions()->where('category', 'SALES')->count();
                    });
                @endphp
                
                <!-- Connected Stores -->
                <div class="stat-card">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="icon-box icon-box-green">
                            @svg('heroicon-o-building-storefront', 'w-6 h-6')
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600">Connected Stores</h3>
                            <p class="text-xs text-gray-500">Active Shopify stores</p>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900">
                        {{ $shopifyStores->count() }}
                    </div>
                </div>
                
                <!-- Total Revenue -->
                <div class="stat-card">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="icon-box icon-box-blue">
                            @svg('heroicon-o-currency-dollar', 'w-6 h-6')
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600">Total Revenue</h3>
                            <p class="text-xs text-gray-500">All-time sales</p>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900">
                        {{ number_format($totalRevenue, 2) }} <span class="text-lg text-gray-600">USD</span>
                    </div>
                </div>
                
                <!-- Total Orders -->
                <div class="stat-card">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-purple-100 p-3 rounded-lg">
                            @svg('heroicon-o-shopping-cart', 'w-6 h-6 text-purple-600')
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600">Total Orders</h3>
                            <p class="text-xs text-gray-500">Synced transactions</p>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900">
                        {{ number_format($totalOrders) }}
                    </div>
                </div>
            </div>
            
            <!-- Store List -->
            <div class="custom-card">
                <h3 class="text-lg font-semibold text-gray-900">Your Shopify Stores</h3>
                <p class="text-sm text-gray-600 mt-1">Manage and monitor your connected stores</p>
                
                <div class="space-y-4 mt-6">
                    @foreach($shopifyStores as $store)
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    @svg('heroicon-o-check-circle', 'w-6 h-6 text-green-600')
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $store->name }}</h4>
                                    <p class="text-sm text-gray-500">{{ $store->shopify_domain }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="custom-badge custom-badge-green">Active</span>
                                <a href="{{ route('filament.admin.resources.stores.edit', $store) }}" 
                                   class="text-blue-600 hover:text-blue-800">
                                    @svg('heroicon-o-pencil', 'w-5 h-5')
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>