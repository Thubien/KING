<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Import Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                <div class="flex items-center">
                    <x-heroicon-o-document-text class="w-5 h-5 text-gray-500 mr-2" />
                    <div>
                        <p class="text-sm font-medium text-gray-500">Supported Formats</p>
                        <p class="text-lg font-bold">Payoneer, Mercury, Stripe</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                <div class="flex items-center">
                    <x-heroicon-o-shield-check class="w-5 h-5 text-success-500 mr-2" />
                    <div>
                        <p class="text-sm font-medium text-gray-500">Auto-Detection</p>
                        <p class="text-lg font-bold">100% Accuracy</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                <div class="flex items-center">
                    <x-heroicon-o-clock class="w-5 h-5 text-warning-500 mr-2" />
                    <div>
                        <p class="text-sm font-medium text-gray-500">Processing Time</p>
                        <p class="text-lg font-bold">< 1 minute</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Import Form --}}
        <form wire:submit="import">
            {{ $this->form }}
            
            <div class="mt-6 flex justify-end gap-3">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-arrow-up-tray">
                    Start Import
                </x-filament::button>
            </div>
        </form>

        {{-- Help Section --}}
        <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-6 mt-8">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Import Guide</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Supported Banks & Formats</h4>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start">
                            <x-heroicon-m-check-circle class="w-4 h-4 text-success-500 mr-2 mt-0.5 flex-shrink-0" />
                            <span><strong>Payoneer:</strong> EUR & USD transaction exports</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-m-check-circle class="w-4 h-4 text-success-500 mr-2 mt-0.5 flex-shrink-0" />
                            <span><strong>Mercury Bank:</strong> Full transaction history</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-m-check-circle class="w-4 h-4 text-success-500 mr-2 mt-0.5 flex-shrink-0" />
                            <span><strong>Stripe:</strong> Balance history & payment reports</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-m-check-circle class="w-4 h-4 text-success-500 mr-2 mt-0.5 flex-shrink-0" />
                            <span><strong>Generic CSV:</strong> Standard bank statements</span>
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Import Process</h4>
                    <ol class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start">
                            <span class="text-gray-500 mr-2">1.</span>
                            <span>Export transactions from your bank/processor</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-gray-500 mr-2">2.</span>
                            <span>Upload the CSV file (auto-detection recommended)</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-gray-500 mr-2">3.</span>
                            <span>Transactions import as "pending" status</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-gray-500 mr-2">4.</span>
                            <span>Use Transaction Editor to assign stores & categories</span>
                        </li>
                    </ol>
                </div>
            </div>
            
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="flex">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0" />
                    <div class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>Pro Tip:</strong> After importing, use the Transaction Editor's bulk actions to quickly assign multiple transactions at once. The system learns from your assignments to provide better suggestions over time.
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // File drop zone enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) {
                const dropZone = fileInput.closest('.filepond--root');
                if (dropZone) {
                    dropZone.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        dropZone.classList.add('ring-2', 'ring-primary-500');
                    });
                    
                    dropZone.addEventListener('dragleave', (e) => {
                        e.preventDefault();
                        dropZone.classList.remove('ring-2', 'ring-primary-500');
                    });
                }
            }
        });
    </script>
    @endpush
</x-filament-panels::page>