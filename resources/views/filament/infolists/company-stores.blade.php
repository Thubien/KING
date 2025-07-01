<div class="space-y-3">
    @forelse($getRecord()->stores as $store)
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <div class="flex items-start justify-between">
                <div>
                    <h4 class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $store->name }}</h4>
                    @if($store->shopify_domain)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $store->shopify_domain }}</p>
                    @endif
                    <div class="flex items-center space-x-2 mt-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $store->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' }}">
                            {{ $store->status === 'active' ? 'Aktif' : 'Pasif' }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $store->currency }}</span>
                    </div>
                </div>
                <div class="text-right">
                    @php
                        $revenue = $store->transactions()->where('type', 'income')->where('category', 'SALES')->sum('amount');
                        $profit = $revenue - $store->transactions()->where('type', 'expense')->sum('amount');
                    @endphp
                    <p class="text-xs text-gray-500 dark:text-gray-400">Gelir</p>
                    <p class="font-semibold text-sm text-gray-900 dark:text-gray-100">
                        {{ Number::currency($revenue, $store->currency) }}
                    </p>
                    <p class="text-xs {{ $profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} mt-1">
                        Kar: {{ Number::currency(abs($profit), $store->currency) }}
                    </p>
                </div>
            </div>
        </div>
    @empty
        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz mağaza yok</p>
    @endforelse
</div>