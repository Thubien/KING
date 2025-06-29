<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Transaction Info --}}
        <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-6">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Transaction Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Date</p>
                    <p class="text-sm font-medium">{{ $transaction->transaction_date->format('M j, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Description</p>
                    <p class="text-sm font-medium">{{ $transaction->description }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Amount</p>
                    <p class="text-sm font-medium {{ $transaction->amount < 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $transaction->currency }} {{ number_format(abs($transaction->amount), 2) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Split Form --}}
        <form wire:submit="save">
            {{ $this->form }}

            <div class="flex items-center justify-between mt-6">
                <div class="text-sm text-gray-500">
                    Ensure all percentages add up to 100%
                </div>
                <div class="flex gap-3">
                    <x-filament::button
                        color="gray"
                        wire:click="cancel"
                        type="button"
                    >
                        Cancel
                    </x-filament::button>
                    
                    <x-filament::button
                        type="submit"
                        icon="heroicon-m-check"
                    >
                        Save Split
                    </x-filament::button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        // Auto-calculate remaining percentage
        document.addEventListener('input', function(e) {
            if (e.target.matches('input[name*="percentage"]')) {
                const inputs = document.querySelectorAll('input[name*="percentage"]');
                let total = 0;
                inputs.forEach(input => {
                    total += parseFloat(input.value) || 0;
                });
                
                // Update visual feedback
                const totalElement = document.querySelector('.percentage-total');
                if (totalElement) {
                    totalElement.textContent = total.toFixed(2) + '%';
                    totalElement.classList.toggle('text-red-600', Math.abs(total - 100) > 0.01);
                    totalElement.classList.toggle('text-green-600', Math.abs(total - 100) <= 0.01);
                }
            }
        });
    </script>
    @endpush
</x-filament-panels::page>