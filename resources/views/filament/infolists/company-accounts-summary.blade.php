<div class="space-y-4">
    @if($bankAccounts->isNotEmpty() || $paymentProcessors->isNotEmpty())
        <div class="space-y-3">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Banka Hesapları</h4>
            @forelse($bankAccounts as $account)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div>
                        <p class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $account->bank_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $account->account_name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-sm {{ $account->current_balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ Number::currency($account->current_balance, $account->currency) }}
                        </p>
                        @if($account->is_primary)
                            <span class="text-xs bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200 px-2 py-0.5 rounded">Ana Hesap</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-gray-500 dark:text-gray-400">Banka hesabı yok</p>
            @endforelse
        </div>

        @if($paymentProcessors->isNotEmpty())
            <div class="space-y-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Ödeme İşlemcileri</h4>
                @foreach($paymentProcessors as $processor)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div>
                            <p class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $processor->processor_type }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Bekleyen: {{ Number::currency($processor->pending_balance, $processor->currency) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-sm text-blue-600 dark:text-blue-400">
                                {{ Number::currency($processor->current_balance, $processor->currency) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Kullanılabilir</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Toplam Bakiye</span>
                <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ Number::currency(
                        $bankAccounts->sum('current_balance') + 
                        $paymentProcessors->sum('current_balance') + 
                        $paymentProcessors->sum('pending_balance'), 
                        $currency
                    ) }}
                </span>
            </div>
        </div>
    @else
        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz hesap bilgisi yok</p>
    @endif
</div>