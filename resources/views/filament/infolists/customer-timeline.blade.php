<div class="space-y-4">
    @forelse($events as $event)
        <div class="relative">
            <div class="flex items-start space-x-3">
                {{-- Timeline line --}}
                @if(!$loop->last)
                    <div class="absolute top-6 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                @endif
                
                {{-- Event icon --}}
                <div class="relative flex h-8 w-8 items-center justify-center rounded-full
                    @switch($event->event_type)
                        @case('order_placed')
                            bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-300
                            @break
                        @case('return_requested')
                        @case('return_completed')
                            bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-300
                            @break
                        @case('note_added')
                            bg-blue-100 text-blue-600 dark:bg-blue-800 dark:text-blue-300
                            @break
                        @case('tag_added')
                        @case('tag_removed')
                            bg-purple-100 text-purple-600 dark:bg-purple-800 dark:text-purple-300
                            @break
                        @case('message_sent')
                            bg-indigo-100 text-indigo-600 dark:bg-indigo-800 dark:text-indigo-300
                            @break
                        @case('status_changed')
                            bg-yellow-100 text-yellow-600 dark:bg-yellow-800 dark:text-yellow-300
                            @break
                        @default
                            bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300
                    @endswitch
                ">
                    @switch($event->event_type)
                        @case('order_placed')
                            <x-heroicon-m-shopping-bag class="h-4 w-4" />
                            @break
                        @case('return_requested')
                        @case('return_completed')
                            <x-heroicon-m-arrow-uturn-left class="h-4 w-4" />
                            @break
                        @case('note_added')
                            <x-heroicon-m-document-text class="h-4 w-4" />
                            @break
                        @case('tag_added')
                        @case('tag_removed')
                            <x-heroicon-m-tag class="h-4 w-4" />
                            @break
                        @case('message_sent')
                            <x-heroicon-m-chat-bubble-left-ellipsis class="h-4 w-4" />
                            @break
                        @case('status_changed')
                            <x-heroicon-m-arrow-path class="h-4 w-4" />
                            @break
                        @default
                            <x-heroicon-m-information-circle class="h-4 w-4" />
                    @endswitch
                </div>
                
                {{-- Event content --}}
                <div class="flex-1 space-y-1">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $event->event_title }}
                        </h4>
                        <time class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $event->created_at->diffForHumans() }}
                        </time>
                    </div>
                    
                    @if($event->event_description)
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $event->event_description }}
                        </p>
                    @endif
                    
                    {{-- Event metadata --}}
                    @if($event->event_data && count($event->event_data) > 0)
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($event->event_data as $key => $value)
                                @if($value && !in_array($key, ['user_id', 'user_name']))
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        @switch($key)
                                            @case('amount')
                                                Tutar: {{ number_format($value, 2) }}
                                                @break
                                            @case('currency')
                                                {{ strtoupper($value) }}
                                                @break
                                            @case('payment_method')
                                                @php
                                                    $methods = [
                                                        'cash' => 'Nakit',
                                                        'credit_card' => 'Kredi Kartı',
                                                        'bank_transfer' => 'Havale/EFT',
                                                        'cash_on_delivery' => 'Kapıda Ödeme',
                                                        'cargo_collect' => 'Kargo Tahsilatlı',
                                                        'crypto' => 'Kripto Para',
                                                        'installment' => 'Taksitli',
                                                        'store_credit' => 'Mağaza Kredisi',
                                                        'other' => 'Diğer',
                                                    ];
                                                @endphp
                                                Ödeme: {{ $methods[$value] ?? $value }}
                                                @break
                                            @case('sales_channel')
                                                @php
                                                    $channels = [
                                                        'shopify' => 'Shopify',
                                                        'instagram' => 'Instagram',
                                                        'telegram' => 'Telegram',
                                                        'whatsapp' => 'WhatsApp',
                                                        'facebook' => 'Facebook',
                                                        'physical' => 'Fiziksel Mağaza',
                                                        'referral' => 'Referans',
                                                        'other' => 'Diğer',
                                                    ];
                                                @endphp
                                                Kanal: {{ $channels[$value] ?? $value }}
                                                @break
                                            @case('refund_method')
                                                @php
                                                    $methods = [
                                                        'cash' => 'Nakit İade',
                                                        'exchange' => 'Değişim',
                                                        'store_credit' => 'Mağaza Kredisi',
                                                    ];
                                                @endphp
                                                {{ $methods[$value] ?? $value }}
                                                @break
                                            @case('store_credit_code')
                                                Kod: {{ $value }}
                                                @break
                                            @case('product_name')
                                                Ürün: {{ $value }}
                                                @break
                                            @case('return_reason')
                                                Sebep: {{ $value }}
                                                @break
                                            @case('tag')
                                                #{{ $value }}
                                                @break
                                            @case('old_status')
                                                {{ ucfirst($value) }} →
                                                @break
                                            @case('new_status')
                                                {{ ucfirst($value) }}
                                                @break
                                            @default
                                                {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}
                                        @endswitch
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    
                    {{-- User info --}}
                    @if($event->creator)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $event->creator->name }}
                        </p>
                    @endif
                    
                    {{-- Related record link --}}
                    @if($event->related_model && $event->related_id)
                        <div class="mt-2">
                            @switch($event->related_model)
                                @case('Transaction')
                                    <a href="{{ route('filament.admin.resources.transactions.edit', $event->related_id) }}" 
                                       class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                        İşlem detaylarını görüntüle →
                                    </a>
                                    @break
                                @case('ReturnRequest')
                                    <a href="{{ route('filament.admin.resources.return-requests.edit', $event->related_id) }}" 
                                       class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                        İade detaylarını görüntüle →
                                    </a>
                                    @break
                            @endswitch
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <x-heroicon-o-clock class="mx-auto h-12 w-12 mb-3" />
            <p>Henüz aktivite bulunmuyor</p>
        </div>
    @endforelse
</div>