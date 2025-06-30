<div class="space-y-4 p-4">
    <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Tutar</p>
            <p class="text-lg font-semibold">{{ number_format($credit->amount, 2) }} {{ $credit->currency }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Kalan Tutar</p>
            <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                {{ number_format($credit->remaining_amount, 2) }} {{ $credit->currency }}
            </p>
        </div>
    </div>

    @if(is_array($credit->usage_history) && count($credit->usage_history) > 0)
        <div class="space-y-2">
            <h4 class="font-medium text-gray-900 dark:text-gray-100">Kullanım Geçmişi</h4>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($credit->usage_history as $usage)
                    <div class="py-3">
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    -{{ number_format($usage['amount'] ?? 0, 2) }} {{ $credit->currency }}
                                </p>
                                @if(isset($usage['notes']) && $usage['notes'])
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $usage['notes'] }}</p>
                                @endif
                                @if(isset($usage['reference']) && $usage['reference'])
                                    <p class="text-xs text-gray-500 dark:text-gray-500">
                                        Ref: {{ $usage['reference'] }}
                                    </p>
                                @endif
                                <p class="text-xs text-gray-500 dark:text-gray-500">
                                    {{ \Carbon\Carbon::parse($usage['date'])->format('d/m/Y H:i') }}
                                    @if(isset($usage['user']) && $usage['user'])
                                        • {{ $usage['user'] }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Kalan</p>
                                <p class="text-sm font-medium">
                                    {{ number_format($usage['remaining'] ?? 0, 2) }} {{ $credit->currency }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <p>Henüz kullanım bulunmuyor</p>
        </div>
    @endif

    @if($credit->notes)
        <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-1">Notlar</h4>
            <p class="text-sm text-yellow-700 dark:text-yellow-300">{{ $credit->notes }}</p>
        </div>
    @endif
</div>