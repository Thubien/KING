<div class="space-y-3">
    @forelse($getRecord()->partnerships as $partnership)
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                    <span class="text-primary-600 dark:text-primary-400 font-semibold">
                        {{ substr($partnership->user->name, 0, 2) }}
                    </span>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $partnership->user->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($partnership->role) }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold text-primary-600 dark:text-primary-400">%{{ number_format($partnership->ownership_percentage, 1) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Hisse Oranı</p>
            </div>
        </div>
    @empty
        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz ortaklık bilgisi yok</p>
    @endforelse
</div>