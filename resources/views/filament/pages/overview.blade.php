<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Message --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Welcome back, {{ auth()->user()->name }}!
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Here's what's happening with your business today.
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">
                        {{ now()->format('l, F j, Y') }}
                    </p>
                    <p class="text-xs text-gray-400">
                        Last updated: {{ now()->format('g:i A') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Quick Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm">Total Stores</p>
                        <p class="text-2xl font-bold">
                            {{ auth()->user()->company->stores()->count() }}
                        </p>
                    </div>
                    <div class="bg-blue-400 bg-opacity-30 p-3 rounded-lg">
                        <x-heroicon-o-building-storefront class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm">Active Partners</p>
                        <p class="text-2xl font-bold">
                            {{ \App\Models\Partnership::where('status', 'ACTIVE')->count() }}
                        </p>
                    </div>
                    <div class="bg-green-400 bg-opacity-30 p-3 rounded-lg">
                        <x-heroicon-o-user-group class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm">This Month</p>
                        <p class="text-2xl font-bold">
                            ${{ number_format(\App\Models\Transaction::thisMonth()->sum('amount'), 0) }}
                        </p>
                    </div>
                    <div class="bg-purple-400 bg-opacity-30 p-3 rounded-lg">
                        <x-heroicon-o-banknotes class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-amber-100 text-sm">Pending Invitations</p>
                        <p class="text-2xl font-bold">
                            {{ \App\Models\Partnership::pendingInvitation()->count() }}
                        </p>
                    </div>
                    <div class="bg-amber-400 bg-opacity-30 p-3 rounded-lg">
                        <x-heroicon-o-envelope class="w-6 h-6" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions Panel --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('filament.admin.resources.transactions.create') }}" 
                   class="flex items-center p-4 border-2 border-dashed border-gray-200 rounded-lg hover:border-green-300 hover:bg-green-50 transition-colors group">
                    <div class="flex items-center space-x-3">
                        <div class="bg-green-100 p-2 rounded-lg group-hover:bg-green-200">
                            <x-heroicon-o-plus-circle class="w-5 h-5 text-green-600" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Add Transaction</p>
                            <p class="text-sm text-gray-500">Record a new financial transaction</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('filament.admin.resources.partnerships.create') }}" 
                   class="flex items-center p-4 border-2 border-dashed border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors group">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-100 p-2 rounded-lg group-hover:bg-blue-200">
                            <x-heroicon-o-user-plus class="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Invite Partner</p>
                            <p class="text-sm text-gray-500">Send partnership invitation</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('filament.admin.resources.stores.create') }}" 
                   class="flex items-center p-4 border-2 border-dashed border-gray-200 rounded-lg hover:border-purple-300 hover:bg-purple-50 transition-colors group">
                    <div class="flex items-center space-x-3">
                        <div class="bg-purple-100 p-2 rounded-lg group-hover:bg-purple-200">
                            <x-heroicon-o-building-storefront class="w-5 h-5 text-purple-600" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Add Store</p>
                            <p class="text-sm text-gray-500">Connect a new store location</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>