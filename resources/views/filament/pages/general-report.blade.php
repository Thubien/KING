<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Enhanced Header with Actions --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 rounded-2xl shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 to-purple-600/20"></div>
            <div class="relative p-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/10 backdrop-blur-sm rounded-lg">
                                @svg('heroicon-o-chart-bar', 'w-6 h-6 text-white')
                            </div>
                            <h1 class="text-3xl font-bold text-white">
                                @if($selectedStore)
                                    {{ $selectedStore->name }}
                                @else
                                    Konsolide Finansal Rapor
                                @endif
                            </h1>
                        </div>
                        <p class="text-lg text-gray-300">
                            {{ \Carbon\Carbon::parse($start_date)->locale('tr')->isoFormat('D MMMM YYYY') }} - 
                            {{ \Carbon\Carbon::parse($end_date)->locale('tr')->isoFormat('D MMMM YYYY') }}
                        </p>
                        <div class="flex items-center gap-4 mt-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full">
                                @svg('heroicon-m-calendar-days', 'w-4 h-4')
                                {{ $dayCount }} gün
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full">
                                @svg('heroicon-m-currency-dollar', 'w-4 h-4')
                                {{ $this->getCurrency() }}
                            </span>
                            @if($selectedStore && $selectedStore->platform)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full capitalize">
                                    @svg('heroicon-m-building-storefront', 'w-4 h-4')
                                    {{ $selectedStore->platform }}
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Action Buttons --}}
                    <div class="flex items-center gap-3">
                        <button wire:click="exportReport" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/10 backdrop-blur-sm text-white font-medium rounded-xl hover:bg-white/20 transition-all duration-200 border border-white/20">
                            @svg('heroicon-m-arrow-down-tray', 'w-5 h-5')
                            Excel İndir
                        </button>
                        <button wire:click="printReport" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-gray-900 font-medium rounded-xl hover:bg-gray-100 transition-all duration-200 shadow-lg">
                            @svg('heroicon-m-printer', 'w-5 h-5')
                            Yazdır
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Enhanced KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Total Income Card --}}
            <div class="relative group">
                <div class="absolute -inset-0.5 bg-gradient-to-r from-green-600 to-emerald-600 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-1000"></div>
                <div class="relative bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="p-2 bg-green-100 rounded-lg">
                                    @svg('heroicon-m-arrow-trending-up', 'w-5 h-5 text-green-600')
                                </div>
                                <p class="text-sm font-medium text-gray-600">Toplam Gelir</p>
                            </div>
                            <p class="text-3xl font-bold text-gray-900 mt-2">
                                {{ number_format($stats['total_income'], 2) }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">{{ $this->getCurrency() }}</p>
                            
                            {{-- Mini Chart Placeholder --}}
                            <div class="mt-4 h-12 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg flex items-end justify-between px-2 gap-1">
                                @for($i = 0; $i < 7; $i++)
                                    <div class="w-full bg-gradient-to-t from-green-400 to-emerald-400 rounded-t" 
                                        style="height: {{ rand(30, 100) }}%"></div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Expense Card --}}
            <div class="relative group">
                <div class="absolute -inset-0.5 bg-gradient-to-r from-red-600 to-rose-600 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-1000"></div>
                <div class="relative bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="p-2 bg-red-100 rounded-lg">
                                    @svg('heroicon-m-arrow-trending-down', 'w-5 h-5 text-red-600')
                                </div>
                                <p class="text-sm font-medium text-gray-600">Toplam Gider</p>
                            </div>
                            <p class="text-3xl font-bold text-gray-900 mt-2">
                                {{ number_format($stats['total_expense'], 2) }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">{{ $this->getCurrency() }}</p>
                            
                            {{-- Mini Chart Placeholder --}}
                            <div class="mt-4 h-12 bg-gradient-to-r from-red-50 to-rose-50 rounded-lg flex items-end justify-between px-2 gap-1">
                                @for($i = 0; $i < 7; $i++)
                                    <div class="w-full bg-gradient-to-t from-red-400 to-rose-400 rounded-t" 
                                        style="height: {{ rand(30, 100) }}%"></div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Net Profit Card --}}
            <div class="relative group">
                <div class="absolute -inset-0.5 bg-gradient-to-r {{ $stats['net_profit'] >= 0 ? 'from-blue-600 to-indigo-600' : 'from-orange-600 to-red-600' }} rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-1000"></div>
                <div class="relative bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="p-2 {{ $stats['net_profit'] >= 0 ? 'bg-blue-100' : 'bg-orange-100' }} rounded-lg">
                                    @svg('heroicon-m-banknotes', 'w-5 h-5 ' . ($stats['net_profit'] >= 0 ? 'text-blue-600' : 'text-orange-600'))
                                </div>
                                <p class="text-sm font-medium text-gray-600">Net Kar</p>
                            </div>
                            <p class="text-3xl font-bold {{ $stats['net_profit'] >= 0 ? 'text-gray-900' : 'text-red-900' }} mt-2">
                                {{ $stats['net_profit'] >= 0 ? '' : '-' }}{{ number_format(abs($stats['net_profit']), 2) }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">{{ $this->getCurrency() }}</p>
                            
                            {{-- Profit Margin --}}
                            @php
                                $profitMargin = $stats['total_income'] > 0 ? ($stats['net_profit'] / $stats['total_income'] * 100) : 0;
                            @endphp
                            <div class="mt-4">
                                <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                    <span>Kar Marjı</span>
                                    <span class="font-semibold">{{ number_format($profitMargin, 1) }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="h-full {{ $profitMargin >= 0 ? 'bg-gradient-to-r from-blue-500 to-indigo-500' : 'bg-gradient-to-r from-orange-500 to-red-500' }} rounded-full transition-all duration-500" 
                                        style="width: {{ min(abs($profitMargin), 100) }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Store & Date Filters in Tabs --}}
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100">
            <div x-data="{ activeTab: 'stores' }" class="p-6">
                {{-- Tab Headers --}}
                <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-xl mb-6">
                    <button @click="activeTab = 'stores'" 
                        :class="activeTab === 'stores' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 font-medium rounded-lg transition-all duration-200">
                        @svg('heroicon-m-building-storefront', 'w-5 h-5')
                        Mağazalar
                    </button>
                    <button @click="activeTab = 'dates'" 
                        :class="activeTab === 'dates' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 font-medium rounded-lg transition-all duration-200">
                        @svg('heroicon-m-calendar', 'w-5 h-5')
                        Tarih Aralığı
                    </button>
                </div>

                {{-- Tab Contents --}}
                <div x-show="activeTab === 'stores'" x-transition>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        <button wire:click="selectStore()" 
                            class="relative group overflow-hidden rounded-xl transition-all duration-300 {{ !$store_id ? 'ring-2 ring-blue-500 ring-offset-2' : '' }}">
                            <div class="absolute inset-0 bg-gradient-to-br {{ !$store_id ? 'from-blue-600 to-purple-600' : 'from-gray-100 to-gray-200' }} opacity-90"></div>
                            <div class="relative px-4 py-3 text-center">
                                <p class="font-semibold {{ !$store_id ? 'text-white' : 'text-gray-700' }}">Tüm Mağazalar</p>
                                <p class="text-xs {{ !$store_id ? 'text-blue-100' : 'text-gray-500' }} mt-0.5">Konsolide Görünüm</p>
                            </div>
                        </button>
                        
                        @foreach($stores as $store)
                            <button wire:click="selectStore({{ $store->id }})" 
                                class="relative group overflow-hidden rounded-xl transition-all duration-300 {{ $store_id == $store->id ? 'ring-2 ring-blue-500 ring-offset-2' : '' }}">
                                <div class="absolute inset-0 bg-gradient-to-br {{ $store_id == $store->id ? 'from-blue-600 to-purple-600' : 'from-gray-100 to-gray-200' }} opacity-90"></div>
                                <div class="relative px-4 py-3 text-center">
                                    <p class="font-semibold {{ $store_id == $store->id ? 'text-white' : 'text-gray-700' }}">{{ $store->name }}</p>
                                    <p class="text-xs {{ $store_id == $store->id ? 'text-blue-100' : 'text-gray-500' }} mt-0.5">{{ $store->currency }}</p>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div x-show="activeTab === 'dates'" x-transition>
                    {{-- Quick Date Presets --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2 mb-6">
                        @foreach(['today' => 'Bugün', 'yesterday' => 'Dün', 'last_7_days' => 'Son 7 Gün', 'last_30_days' => 'Son 30 Gün', 'last_month' => 'Geçen Ay', 'last_3_months' => 'Son 3 Ay', 'last_6_months' => 'Son 6 Ay', 'this_year' => 'Bu Yıl'] as $preset => $label)
                            <button wire:click="setDatePreset('{{ $preset }}')" 
                                class="px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ $selected_preset == $preset ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/25' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Custom Date Range --}}
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Başlangıç Tarihi</label>
                                <div class="relative">
                                    <input type="date" wire:model="start_date" wire:change="updateDates"
                                        class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        @svg('heroicon-m-calendar', 'w-5 h-5 text-gray-400')
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bitiş Tarihi</label>
                                <div class="relative">
                                    <input type="date" wire:model="end_date" wire:change="updateDates"
                                        class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        @svg('heroicon-m-calendar', 'w-5 h-5 text-gray-400')
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-end">
                                <button wire:click="refreshReport" 
                                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-500/20 transition-all duration-200 shadow-lg shadow-blue-600/25">
                                    @svg('heroicon-m-arrow-path', 'w-5 h-5')
                                    Raporu Güncelle
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Enhanced Financial Table --}}
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Detaylı Finansal Analiz</h3>
                        <p class="text-sm text-gray-600 mt-1">Günlük bazda kategori dağılımı ve kar analizi</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500">{{ $dailyData->count() }} kayıt</span>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-m-calendar-days', 'w-4 h-4 text-gray-400')
                                    <span class="font-semibold text-gray-700 text-sm">TARİH</span>
                                </div>
                            </th>
                            @php
                                $categories = [
                                    'SALES' => ['color' => 'emerald', 'icon' => 'banknotes'],
                                    'RETURNS' => ['color' => 'orange', 'icon' => 'arrow-uturn-left'],
                                    'PAY-PRODUCT' => ['color' => 'yellow', 'icon' => 'cube'],
                                    'PAY-DELIVERY' => ['color' => 'blue', 'icon' => 'truck'],
                                    'WITHDRAW' => ['color' => 'purple', 'icon' => 'user-minus'],
                                    'BANK FEE' => ['color' => 'indigo', 'icon' => 'building-library'],
                                    'FEE' => ['color' => 'pink', 'icon' => 'credit-card'],
                                    'ADS' => ['color' => 'cyan', 'icon' => 'megaphone'],
                                    'OTHER PAY' => ['color' => 'gray', 'icon' => 'ellipsis-horizontal-circle'],
                                ];
                            @endphp
                            
                            @foreach($categories as $category => $config)
                                <th class="px-4 py-4 text-right">
                                    <button wire:click="viewCategoryDetails('{{ str_replace(' ', '_', $category) }}')" 
                                        class="group flex items-center gap-1.5 text-xs font-semibold text-{{ $config['color'] }}-700 hover:text-{{ $config['color'] }}-900 transition-colors">
                                        @svg('heroicon-m-' . $config['icon'], 'w-4 h-4 opacity-50 group-hover:opacity-100')
                                        <span>{{ $category }}</span>
                                    </button>
                                </th>
                            @endforeach
                            
                            <th class="sticky right-0 z-10 bg-gray-900 px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @svg('heroicon-m-chart-bar', 'w-4 h-4 text-gray-400')
                                    <span class="font-semibold text-white text-sm">NET KAR</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($dailyData as $index => $day)
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                <td class="sticky left-0 z-10 bg-white group-hover:bg-gray-50/50 px-6 py-4">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($day->date)->locale('tr')->isoFormat('D MMMM') }}</p>
                                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($day->date)->locale('tr')->isoFormat('dddd') }}</p>
                                    </div>
                                </td>
                                
                                <td class="px-4 py-4 text-right">
                                    @if($day->sales > 0)
                                        <span class="font-semibold text-emerald-600">{{ number_format($day->sales, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <td class="px-4 py-4 text-right">
                                    @if($day->returns > 0)
                                        <span class="font-semibold text-orange-600">{{ number_format($day->returns, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <td class="px-4 py-4 text-right">
                                    @if($day->pay_product > 0)
                                        <span class="font-semibold text-yellow-600">{{ number_format($day->pay_product, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <td class="px-4 py-4 text-right">
                                    @if($day->pay_delivery > 0)
                                        <span class="font-semibold text-blue-600">{{ number_format($day->pay_delivery, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <td class="px-4 py-4 text-right">
                                    @if($day->withdraw > 0)
                                        <span class="font-semibold text-purple-600">{{ number_format($day->withdraw, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <td class="px-4 py-4 text-right">
                                    @if($day->bank_fee > 0)
                                        <span class="font-semibold text-indigo-600">{{ number_format($day->bank_fee, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <td class="px-4 py-4 text-right">
                                    @if($day->fee > 0)
                                        <span class="font-semibold text-pink-600">{{ number_format($day->fee, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <td class="px-4 py-4 text-right">
                                    @if($day->ads > 0)
                                        <span class="font-semibold text-cyan-600">{{ number_format($day->ads, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <td class="px-4 py-4 text-right">
                                    @if($day->other_pay > 0)
                                        <span class="font-semibold text-gray-600">{{ number_format($day->other_pay, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <td class="sticky right-0 z-10 bg-gray-900 group-hover:bg-gray-800 px-6 py-4 text-right">
                                    <span class="text-lg font-bold {{ $day->net_profit >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                        {{ $day->net_profit >= 0 ? '+' : '' }}{{ number_format($day->net_profit, 2) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-16 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        @svg('heroicon-o-document-magnifying-glass', 'w-12 h-12 text-gray-300 mb-3')
                                        <p class="text-gray-500 font-medium">Bu tarih aralığında veri bulunamadı</p>
                                        <p class="text-sm text-gray-400 mt-1">Farklı bir tarih aralığı veya mağaza seçmeyi deneyin</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        
                        {{-- Grand Totals Row --}}
                        @if($dailyData->count() > 0)
                            <tr class="bg-gradient-to-r from-gray-900 to-gray-800 text-white">
                                <td class="sticky left-0 z-10 bg-gray-900 px-6 py-5">
                                    <div>
                                        <p class="font-bold text-lg">GENEL TOPLAM</p>
                                        <p class="text-xs text-gray-400">{{ $dayCount }} günlük özet</p>
                                    </div>
                                </td>
                                <td class="px-4 py-5 text-right">
                                    <span class="text-lg font-bold">{{ number_format($totals['sales'], 2) }}</span>
                                </td>
                                <td class="px-4 py-5 text-right">
                                    <span class="text-lg font-bold">{{ number_format($totals['returns'], 2) }}</span>
                                </td>
                                <td class="px-4 py-5 text-right">
                                    <span class="text-lg font-bold">{{ number_format($totals['pay_product'], 2) }}</span>
                                </td>
                                <td class="px-4 py-5 text-right">
                                    <span class="text-lg font-bold">{{ number_format($totals['pay_delivery'], 2) }}</span>
                                </td>
                                <td class="px-4 py-5 text-right">
                                    <span class="text-lg font-bold">{{ number_format($totals['withdraw'], 2) }}</span>
                                </td>
                                <td class="px-4 py-5 text-right">
                                    <span class="text-lg font-bold">{{ number_format($totals['bank_fee'], 2) }}</span>
                                </td>
                                <td class="px-4 py-5 text-right">
                                    <span class="text-lg font-bold">{{ number_format($totals['fee'], 2) }}</span>
                                </td>
                                <td class="px-4 py-5 text-right">
                                    <span class="text-lg font-bold">{{ number_format($totals['ads'], 2) }}</span>
                                </td>
                                <td class="px-4 py-5 text-right">
                                    <span class="text-lg font-bold">{{ number_format($totals['other_pay'], 2) }}</span>
                                </td>
                                <td class="sticky right-0 z-10 bg-gray-900 px-6 py-5 text-right">
                                    <span class="text-xl font-bold {{ $totals['net_profit'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                        {{ $totals['net_profit'] >= 0 ? '+' : '' }}{{ number_format($totals['net_profit'], 2) }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Enhanced Partner Profit Distribution --}}
        @if($store_id && $selectedStore && $selectedStore->activePartnerships->count() > 0)
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Partner Kar Dağılımı</h3>
                            <p class="text-sm text-gray-600 mt-1">Hissedarların kar payları ve detaylı analiz</p>
                        </div>
                        <div class="px-3 py-1 bg-purple-100 text-purple-800 text-sm font-medium rounded-full">
                            {{ $selectedStore->activePartnerships->count() }} Partner
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="grid gap-4">
                        @foreach($selectedStore->activePartnerships as $partnership)
                            @php
                                $partnerProfit = $totals['net_profit'] * ($partnership->ownership_percentage / 100);
                                $isProfitable = $partnerProfit >= 0;
                            @endphp
                            <div class="group relative overflow-hidden rounded-xl border border-gray-200 hover:border-gray-300 transition-all duration-300">
                                <div class="absolute inset-0 bg-gradient-to-r {{ $isProfitable ? 'from-emerald-500/5 to-green-500/5' : 'from-red-500/5 to-orange-500/5' }} opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <div class="relative p-5">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-4">
                                            <div class="relative">
                                                <img src="{{ $partnership->user->avatar_url }}" 
                                                    alt="{{ $partnership->user->name }}" 
                                                    class="w-14 h-14 rounded-full ring-4 {{ $isProfitable ? 'ring-emerald-100' : 'ring-red-100' }}">
                                                <div class="absolute -bottom-1 -right-1 w-6 h-6 {{ $isProfitable ? 'bg-emerald-500' : 'bg-red-500' }} rounded-full flex items-center justify-center">
                                                    @svg($isProfitable ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down', 'w-3.5 h-3.5 text-white')
                                                </div>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-gray-900 text-lg">{{ $partnership->user->name }}</h4>
                                                <div class="flex items-center gap-3 mt-1">
                                                    <span class="inline-flex items-center gap-1 text-sm text-gray-600">
                                                        @svg('heroicon-m-chart-pie', 'w-4 h-4')
                                                        %{{ number_format($partnership->ownership_percentage, 2) }} Hisse
                                                    </span>
                                                    <span class="text-gray-300">•</span>
                                                    <span class="text-sm text-gray-600">
                                                        {{ $partnership->user->email }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-right">
                                            <p class="text-2xl font-bold {{ $isProfitable ? 'text-emerald-600' : 'text-red-600' }}">
                                                {{ $isProfitable ? '+' : '-' }}{{ number_format(abs($partnerProfit), 2) }}
                                            </p>
                                            <p class="text-sm text-gray-500 mt-1">{{ $this->getCurrency() }} kar payı</p>
                                        </div>
                                    </div>
                                    
                                    {{-- Mini Stats --}}
                                    <div class="grid grid-cols-3 gap-4 mt-4 pt-4 border-t border-gray-100">
                                        <div>
                                            <p class="text-xs text-gray-500">Günlük Ortalama</p>
                                            <p class="font-semibold text-gray-900">{{ number_format($partnerProfit / $dayCount, 2) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Toplam Gelir Payı</p>
                                            <p class="font-semibold text-gray-900">{{ number_format($stats['total_income'] * ($partnership->ownership_percentage / 100), 2) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Toplam Gider Payı</p>
                                            <p class="font-semibold text-gray-900">{{ number_format($stats['total_expense'] * ($partnership->ownership_percentage / 100), 2) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    {{-- Loading State --}}
    <div wire:loading.flex wire:target="refreshReport,selectStore,setDatePreset" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm">
        <div class="m-auto bg-white rounded-2xl shadow-2xl p-8 flex items-center gap-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="text-gray-700 font-medium">Rapor yükleniyor...</p>
        </div>
    </div>
    
    @push('scripts')
    <script>
        window.addEventListener('print-report', () => {
            window.print();
        });
    </script>
    @endpush
</x-filament-panels::page>