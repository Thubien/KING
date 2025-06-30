<div class="space-y-2">
    @forelse($transactions as $transaction)
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <p class="font-medium text-sm text-gray-900 dark:text-gray-100">
                        {{ $transaction->description ?? $transaction->transaction_id }}
                    </p>
                    <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ $transaction->transaction_date->format('d M Y') }}</span>
                        @if($transaction->customer)
                            <span>•</span>
                            <span>{{ $transaction->customer->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-sm {{ $transaction->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $transaction->type === 'income' ? '+' : '-' }}{{ Number::currency($transaction->amount, $transaction->currency) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ trans('filament.categories.' . $transaction->category, [], 'tr') ?? $transaction->category }}
                    </p>
                </div>
            </div>
        </div>
    @empty
        <p class="text-gray-500 dark:text-gray-400 text-center py-8">Henüz işlem kaydı yok</p>
    @endforelse
</div>