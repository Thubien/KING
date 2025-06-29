<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $stats = $this->getHeaderStats();
            @endphp
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                <div class="flex items-center">
                    <x-heroicon-o-document-text class="w-5 h-5 text-gray-500 mr-2" />
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Transactions</p>
                        <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                <div class="flex items-center">
                    <x-heroicon-o-clock class="w-5 h-5 text-warning-500 mr-2" />
                    <div>
                        <p class="text-sm font-medium text-gray-500">Pending Assignment</p>
                        <p class="text-2xl font-bold text-warning-600">{{ $stats['pending'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                <div class="flex items-center">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-success-500 mr-2" />
                    <div>
                        <p class="text-sm font-medium text-gray-500">Assigned</p>
                        <p class="text-2xl font-bold text-success-600">{{ $stats['assigned'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                <div class="flex items-center">
                    <x-heroicon-o-chart-bar class="w-5 h-5 {{ $stats['progress'] >= 80 ? 'text-success-500' : ($stats['progress'] >= 50 ? 'text-warning-500' : 'text-danger-500') }} mr-2" />
                    <div>
                        <p class="text-sm font-medium text-gray-500">Progress</p>
                        <p class="text-2xl font-bold {{ $stats['progress'] >= 80 ? 'text-success-600' : ($stats['progress'] >= 50 ? 'text-warning-600' : 'text-danger-600') }}">{{ $stats['progress'] }}%</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-2">
                <button wire:click="$refresh" 
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600">
                    <x-heroicon-m-arrow-path class="w-4 h-4 mr-1" />
                    Refresh
                </button>
            </div>
        </div>

        {{-- Transaction Table --}}
        {{ $this->table }}
    </div>

    @push('scripts')
    <script>
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                Livewire.emit('save');
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
