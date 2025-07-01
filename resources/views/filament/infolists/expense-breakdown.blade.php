<div class="space-y-4">
    @if($expenses->isNotEmpty())
        <div class="space-y-3">
            @php
                $total = $expenses->sum('total');
                $colors = [
                    'ADS' => 'bg-blue-500',
                    'PAY-PRODUCT' => 'bg-yellow-500',
                    'PAY-DELIVERY' => 'bg-purple-500',
                    'FEE' => 'bg-red-500',
                    'BANK_COM' => 'bg-gray-500',
                    'WITHDRAW' => 'bg-indigo-500',
                    'OTHER_PAY' => 'bg-green-500',
                ];
            @endphp
            
            @foreach($expenses as $expense)
                @php
                    $percentage = $total > 0 ? ($expense->total / $total) * 100 : 0;
                    $color = $colors[$expense->category] ?? 'bg-gray-400';
                @endphp
                
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 rounded-full {{ $color }}"></div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ trans('filament.categories.' . $expense->category, [], 'tr') ?? $expense->category }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ Number::currency($expense->total, $currency) }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                ({{ number_format($percentage, 1) }}%)
                            </span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="{{ $color }} h-2 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Toplam Gider</span>
                <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ Number::currency($total, $currency) }}
                </span>
            </div>
        </div>
    @else
        <p class="text-gray-500 dark:text-gray-400 text-center py-8">Henüz gider kaydı yok</p>
    @endif
</div>