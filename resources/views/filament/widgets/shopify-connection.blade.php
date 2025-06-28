<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            {{-- Connection Form --}}
            <form wire:submit="connectStore">
                {{ $this->form }}
            </form>
            
            {{-- Connected Stores List --}}
            @if($this->hasConnectedStores())
                <div class="mt-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Connected Shopify Stores
                    </h3>
                    
                    <div class="grid gap-4">
                        @foreach($this->getConnectedStores() as $store)
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                                <x-heroicon-o-building-storefront class="w-6 h-6 text-green-600 dark:text-green-400" />
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $store->name }}
                                            </h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $store->shopify_domain }}
                                            </p>
                                            <div class="flex items-center space-x-4 mt-1">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $store->currency }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    Connected {{ $store->created_at->diffForHumans() }}
                                                </span>
                                                @if($store->last_sync_at)
                                                    <span class="text-xs text-green-600 dark:text-green-400">
                                                        Last sync {{ $store->last_sync_at->diffForHumans() }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('filament.admin.resources.stores.view', $store) }}" 
                                           class="inline-flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 shadow-sm text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                            <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                                            View
                                        </a>
                                        
                                        @can('disconnect', $store)
                                            <form method="POST" action="{{ route('shopify.disconnect', $store) }}" 
                                                  onsubmit="return confirm('Are you sure you want to disconnect this store? Transaction history will be preserved.')">
                                                @csrf
                                                <button type="submit" 
                                                        class="inline-flex items-center px-3 py-1 border border-red-300 dark:border-red-600 shadow-sm text-xs font-medium rounded-md text-red-700 dark:text-red-300 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900">
                                                    <x-heroicon-o-x-mark class="w-4 h-4 mr-1" />
                                                    Disconnect
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                                
                                {{-- Store Stats --}}
                                @if($store->partnerships->count() > 0)
                                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                            <span>{{ $store->partnerships->count() }} Partnership(s)</span>
                                            <span> {{ number_format($store->getRevenue(), 2) }} {{ $store->currency }} this month</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            {{-- Helpful Tips --}}
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex items-start">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-2 flex-shrink-0" />
                    <div class="text-sm text-blue-800 dark:text-blue-200">
                        <p class="font-medium mb-1">Quick Setup Tips:</p>
                        <ul class="space-y-1 list-disc list-inside ml-2">
                            <li>You need admin access to connect a Shopify store</li>
                            <li>After connecting, set up partnerships to define profit sharing</li>
                            <li>Transaction sync happens automatically every hour</li>
                            <li>Manual transaction entry is always available alongside Shopify data</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>