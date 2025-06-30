<div class="space-y-3">
    @forelse($activities as $activity)
        <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="flex-shrink-0">
                @if($activity['type'] === 'transaction')
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                @else
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $activity['title'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $activity['description'] }}</p>
                @if($activity['store'])
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $activity['store'] }}</p>
                @endif
            </div>
            <div class="text-right">
                @if(isset($activity['amount']))
                    <p class="text-sm font-semibold {{ str_contains($activity['title'], 'Gelir') ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ Number::currency($activity['amount'], $activity['currency']) }}
                    </p>
                @endif
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $activity['date']->diffForHumans() }}
                </p>
            </div>
        </div>
    @empty
        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz aktivite yok</p>
    @endforelse
</div>