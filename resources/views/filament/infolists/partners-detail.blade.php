<div class="space-y-4">
    @forelse($partnerships as $partnership)
        @php
            $netProfit = $store->getNetProfit();
            $profitShare = $netProfit * ($partnership->ownership_percentage / 100);
            $personalExpenses = $partnership->user->getPersonalExpensesForStore($store->id);
            $netBalance = $profitShare - $personalExpenses;
        @endphp
        
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                        <span class="text-primary-600 dark:text-primary-400 font-bold text-lg">
                            {{ substr($partnership->user->name, 0, 2) }}
                        </span>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ $partnership->user->name }}</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $partnership->user->email }}</p>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                {{ ucfirst($partnership->role) }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                %{{ number_format($partnership->ownership_percentage, 1) }} Hisse
                            </span>
                        </div>
                    </div>
                </div>
                
                @if($partnership->status === 'ACTIVE')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Aktif
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                        {{ $partnership->status }}
                    </span>
                @endif
            </div>
            
            <div class="grid grid-cols-3 gap-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Kar Payı</p>
                    <p class="text-sm font-semibold {{ $profitShare >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ Number::currency(abs($profitShare), $store->currency) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Kişisel Harcama</p>
                    <p class="text-sm font-semibold text-red-600 dark:text-red-400">
                        {{ Number::currency($personalExpenses, $store->currency) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Net Bakiye</p>
                    <p class="text-sm font-semibold {{ $netBalance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ Number::currency(abs($netBalance), $store->currency) }}
                    </p>
                </div>
            </div>
            
            @if($partnership->partnership_start_date)
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Ortaklık Başlangıcı: {{ $partnership->partnership_start_date->format('d M Y') }}
                        <span class="text-gray-400 dark:text-gray-500">({{ $partnership->partnership_start_date->diffForHumans() }})</span>
                    </p>
                </div>
            @endif
        </div>
    @empty
        <p class="text-gray-500 dark:text-gray-400 text-center py-8">Henüz ortak bilgisi yok</p>
    @endforelse
</div>