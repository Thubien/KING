<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mağaza</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gelir</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gider</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Net Kar</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kar Marjı</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlem</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($stores as $store)
                @php
                    $revenue = $store->transactions->where('type', 'income')->where('category', 'SALES')->sum('amount');
                    $expenses = $store->transactions->where('type', 'expense')->sum('amount');
                    $profit = $revenue - $expenses;
                    $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
                    $transactionCount = $store->transactions->count();
                @endphp
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $store->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $store->currency }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-right">
                        <p class="text-sm font-medium text-green-600 dark:text-green-400">
                            {{ Number::currency($revenue, $store->currency) }}
                        </p>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-right">
                        <p class="text-sm font-medium text-red-600 dark:text-red-400">
                            {{ Number::currency($expenses, $store->currency) }}
                        </p>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-right">
                        <p class="text-sm font-bold {{ $profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ Number::currency(abs($profit), $store->currency) }}
                        </p>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end">
                            <span class="text-sm font-medium {{ $margin >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                %{{ number_format(abs($margin), 1) }}
                            </span>
                            <div class="ml-2 w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="{{ $margin >= 0 ? 'bg-green-500' : 'bg-red-500' }} h-2 rounded-full" style="width: {{ min(abs($margin), 100) }}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-right">
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $transactionCount }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        Henüz mağaza performans verisi yok
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($stores->isNotEmpty())
            <tfoot class="bg-gray-50 dark:bg-gray-800">
                @php
                    $totalRevenue = $stores->sum(fn($s) => $s->transactions->where('type', 'income')->where('category', 'SALES')->sum('amount'));
                    $totalExpenses = $stores->sum(fn($s) => $s->transactions->where('type', 'expense')->sum('amount'));
                    $totalProfit = $totalRevenue - $totalExpenses;
                    $totalMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
                    $totalTransactions = $stores->sum(fn($s) => $s->transactions->count());
                @endphp
                <tr>
                    <td class="px-4 py-3 font-medium text-sm text-gray-900 dark:text-gray-100">Toplam</td>
                    <td class="px-4 py-3 text-right font-bold text-sm text-green-600 dark:text-green-400">
                        {{ Number::currency($totalRevenue, $currency) }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-sm text-red-600 dark:text-red-400">
                        {{ Number::currency($totalExpenses, $currency) }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-sm {{ $totalProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ Number::currency(abs($totalProfit), $currency) }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-sm {{ $totalMargin >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        %{{ number_format(abs($totalMargin), 1) }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-sm text-gray-500 dark:text-gray-400">
                        {{ $totalTransactions }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>