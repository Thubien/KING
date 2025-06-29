<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            {{-- Total Debt Overview --}}
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Debt Overview</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Debt Balance</p>
                        <p class="text-2xl font-bold mt-1 {{ $totalDebt > 0 ? 'text-red-600' : ($totalDebt < 0 ? 'text-green-600' : 'text-gray-900') }}">
                            @if($totalDebt > 0)
                                -USD {{ number_format(abs($totalDebt), 2) }}
                            @elseif($totalDebt < 0)
                                +USD {{ number_format(abs($totalDebt), 2) }}
                            @else
                                USD 0.00
                            @endif
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            @if($totalDebt > 0)
                                You owe money
                            @elseif($totalDebt < 0)
                                You have credit
                            @else
                                No debt
                            @endif
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Active Partnerships</p>
                        <p class="text-2xl font-bold mt-1 text-gray-900 dark:text-gray-100">
                            {{ $partnerships->count() }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Across multiple stores
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pending Settlements</p>
                        <p class="text-2xl font-bold mt-1 {{ $pendingSettlements > 0 ? 'text-warning-600' : 'text-gray-900' }}">
                            {{ $pendingSettlements }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Awaiting approval
                        </p>
                    </div>
                </div>
            </div>
            
            {{-- Partnership Details --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Partnership Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($partnerships as $partnership)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $partnership->store->name }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $partnership->ownership_percentage }}% ownership
                                    </p>
                                </div>
                                <span class="text-lg font-bold {{ $partnership->hasDebt() ? 'text-red-600' : ($partnership->hasCredit() ? 'text-green-600' : 'text-gray-900') }}">
                                    {{ $partnership->getFormattedDebtBalance() }}
                                </span>
                            </div>
                            
                            @if($partnership->settlements->count() > 0)
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Recent activity:</p>
                                    <div class="space-y-1">
                                        @foreach($partnership->settlements->take(3) as $settlement)
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="text-gray-600 dark:text-gray-400">
                                                    {{ ucfirst($settlement->settlement_type) }}
                                                </span>
                                                <span class="{{ $settlement->settlement_type === 'payment' ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ $settlement->getFormattedAmount() }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <div class="mt-3 flex space-x-2">
                                <a href="/admin/settlements/create?partnership_id={{ $partnership->id }}" 
                                   class="text-xs text-primary-600 hover:text-primary-700 font-medium">
                                    New Settlement →
                                </a>
                                @if($partnership->debt_last_updated_at)
                                    <span class="text-xs text-gray-400">
                                        Updated {{ $partnership->debt_last_updated_at->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- Recent Settlements --}}
            @if($recentSettlements->count() > 0)
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Settlements</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-2 text-gray-600 dark:text-gray-400">Date</th>
                                    <th class="text-left py-2 text-gray-600 dark:text-gray-400">Store</th>
                                    <th class="text-left py-2 text-gray-600 dark:text-gray-400">Type</th>
                                    <th class="text-right py-2 text-gray-600 dark:text-gray-400">Amount</th>
                                    <th class="text-left py-2 text-gray-600 dark:text-gray-400">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($recentSettlements as $settlement)
                                    <tr>
                                        <td class="py-2 text-gray-900 dark:text-gray-100">
                                            {{ $settlement->created_at->format('M j') }}
                                        </td>
                                        <td class="py-2 text-gray-600 dark:text-gray-400">
                                            {{ $settlement->partnership->store->name }}
                                        </td>
                                        <td class="py-2">
                                            <span class="text-xs px-2 py-1 rounded-full bg-{{ $settlement->getTypeColor() }}-100 text-{{ $settlement->getTypeColor() }}-700">
                                                {{ ucfirst($settlement->settlement_type) }}
                                            </span>
                                        </td>
                                        <td class="py-2 text-right font-medium {{ $settlement->settlement_type === 'payment' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $settlement->getFormattedAmount() }}
                                        </td>
                                        <td class="py-2">
                                            <span class="text-xs px-2 py-1 rounded-full bg-{{ $settlement->getStatusColor() }}-100 text-{{ $settlement->getStatusColor() }}-700">
                                                {{ ucfirst($settlement->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <a href="/admin/settlements" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                            View all settlements →
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>