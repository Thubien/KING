<x-filament-panels::page.simple>
    <div class="space-y-8">
        <!-- Logo & Branding -->
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ config('app.name', 'Shopletix') }}
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Multi-Store Financial Management Platform
            </p>
        </div>

        @if (session('status'))
            <x-filament::alert color="success">
                {{ session('status') }}
            </x-filament::alert>
        @endif

        <!-- Login Form -->
        <form wire:submit="authenticate" class="space-y-6">
            {{ $this->form }}

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <x-filament::input.checkbox wire:model="remember" />
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                        Remember me
                    </span>
                </label>

                <a href="{{ route('filament.admin.auth.password.request') }}" 
                   class="text-sm font-medium text-primary-600 hover:text-primary-500">
                    Forgot password?
                </a>
            </div>

            <x-filament::button type="submit" class="w-full">
                Sign in
            </x-filament::button>

            <div class="text-center">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    Don't have an account?
                </span>
                <a href="{{ route('filament.admin.auth.register') }}" 
                   class="ml-1 text-sm font-medium text-primary-600 hover:text-primary-500">
                    Start free trial
                </a>
            </div>
        </form>

        <!-- Features -->
        <div class="mt-8 grid grid-cols-3 gap-4 text-center">
            <div>
                <div class="text-2xl font-bold text-primary-600">1000+</div>
                <div class="text-xs text-gray-600">Active Stores</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-primary-600">$10M+</div>
                <div class="text-xs text-gray-600">Transactions</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-primary-600">99.9%</div>
                <div class="text-xs text-gray-600">Uptime</div>
            </div>
        </div>
    </div>
</x-filament-panels::page.simple>