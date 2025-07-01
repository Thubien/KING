<x-filament-panels::page.simple>
    <div class="space-y-8">
        <!-- Logo & Branding -->
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Start Your Free Trial
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                14 days free • No credit card required • Cancel anytime
            </p>
        </div>

        <!-- Benefits -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="flex items-center">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2" />
                    <span>Multi-store management</span>
                </div>
                <div class="flex items-center">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2" />
                    <span>Real-time analytics</span>
                </div>
                <div class="flex items-center">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2" />
                    <span>Partnership tracking</span>
                </div>
                <div class="flex items-center">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2" />
                    <span>Automated imports</span>
                </div>
            </div>
        </div>

        <!-- Register Form -->
        <form wire:submit="register" class="space-y-6">
            {{ $this->form }}

            <div class="space-y-2">
                <label class="flex items-start">
                    <x-filament::input.checkbox wire:model="terms" class="mt-1" required />
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                        I agree to the 
                        <a href="#" class="underline hover:text-primary-600">Terms of Service</a> 
                        and 
                        <a href="#" class="underline hover:text-primary-600">Privacy Policy</a>
                    </span>
                </label>
            </div>

            <x-filament::button type="submit" class="w-full">
                Create Account
            </x-filament::button>

            <div class="text-center">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    Already have an account?
                </span>
                <a href="{{ route('filament.admin.auth.login') }}" 
                   class="ml-1 text-sm font-medium text-primary-600 hover:text-primary-500">
                    Sign in
                </a>
            </div>
        </form>

        <!-- Security Note -->
        <div class="text-center text-xs text-gray-500">
            <x-heroicon-o-lock-closed class="w-4 h-4 inline mr-1" />
            Your data is encrypted and secure
        </div>
    </div>
</x-filament-panels::page.simple>