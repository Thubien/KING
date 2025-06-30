<div class="space-y-3">
    @forelse($getRecord()->users as $user)
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                    <span class="text-primary-600 dark:text-primary-400 font-semibold">
                        {{ substr($user->name, 0, 2) }}
                    </span>
                </div>
                <div>
                    <p class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                </div>
            </div>
            <div class="text-right">
                @if($user->roles->isNotEmpty())
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                        {{ $user->roles->first()->name }}
                    </span>
                @endif
                @if($user->is_active)
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">Aktif</p>
                @else
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">Pasif</p>
                @endif
            </div>
        </div>
    @empty
        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz kullanıcı yok</p>
    @endforelse
    
    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Toplam {{ $getRecord()->users->count() }} kullanıcı
        </p>
    </div>
</div>