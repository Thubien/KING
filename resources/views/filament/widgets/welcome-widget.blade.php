<x-filament-widgets::widget>
    <x-filament::section>
        <div class="text-center py-12">
            <div class="mx-auto w-24 h-24 bg-primary-100 rounded-full flex items-center justify-center mb-6">
                <x-heroicon-o-building-storefront class="w-12 h-12 text-primary-600" />
            </div>
            
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Welcome to {{ config('app.name') }}!
            </h2>
            
            <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-2xl mx-auto">
                You're on a 14-day free trial. Let's get started by creating your first store 
                and importing your financial data.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <x-filament::button
                    href="{{ route('filament.admin.resources.stores.create') }}"
                    size="lg"
                    icon="heroicon-o-plus-circle"
                >
                    Create Your First Store
                </x-filament::button>
                
                <x-filament::button
                    href="#"
                    size="lg"
                    color="gray"
                    icon="heroicon-o-play-circle"
                    x-on:click="$dispatch('open-modal', { id: 'onboarding-video' })"
                >
                    Watch Tutorial
                </x-filament::button>
            </div>
            
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <div class="text-left">
                    <div class="flex items-center mb-2">
                        <div class="w-8 h-8 bg-success-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-sm font-bold text-success-700">1</span>
                        </div>
                        <h3 class="font-semibold">Create Store</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 ml-11">
                        Add your Shopify or manual store details
                    </p>
                </div>
                
                <div class="text-left">
                    <div class="flex items-center mb-2">
                        <div class="w-8 h-8 bg-info-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-sm font-bold text-info-700">2</span>
                        </div>
                        <h3 class="font-semibold">Import Data</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 ml-11">
                        Upload bank statements or connect APIs
                    </p>
                </div>
                
                <div class="text-left">
                    <div class="flex items-center mb-2">
                        <div class="w-8 h-8 bg-warning-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-sm font-bold text-warning-700">3</span>
                        </div>
                        <h3 class="font-semibold">Invite Partners</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 ml-11">
                        Add business partners and set permissions
                    </p>
                </div>
            </div>
        </div>
    </x-filament::section>
    
    <x-filament::modal id="onboarding-video" width="4xl">
        <x-slot name="heading">
            Getting Started Tutorial
        </x-slot>
        
        <div class="aspect-w-16 aspect-h-9">
            <iframe 
                src="https://www.youtube.com/embed/dQw4w9WgXcQ" 
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen
                class="w-full h-96"
            ></iframe>
        </div>
    </x-filament::modal>
</x-filament-widgets::widget>