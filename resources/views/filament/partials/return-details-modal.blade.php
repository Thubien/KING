<div class="modal-header bg-gradient-to-r from-purple-600 to-blue-600 text-white p-6 rounded-t-2xl">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-2xl font-bold">#{{ $return->order_number }}</h3>
            <p class="text-purple-100 mt-1">{{ $return->customer_name }}</p>
        </div>
        <button onclick="closeReturnDetails()" class="text-white hover:bg-white/20 p-2 rounded-lg transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>

<div class="p-6 max-h-[70vh] overflow-y-auto">
    <!-- İade Detayları -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <h4 class="font-semibold text-gray-700 mb-3">İade Bilgileri</h4>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-500">Ürün:</span>
                    <span class="font-medium">{{ $return->product_name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">İade Nedeni:</span>
                    <span class="font-medium">{{ $return->return_reason }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Durum:</span>
                    <span class="font-medium">{{ $return->status_label }}</span>
                </div>
                @if($return->resolution)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Çözüm:</span>
                        <span class="font-medium">{{ $return->resolution_label }}</span>
                    </div>
                @endif
            </div>
        </div>
        
        <div>
            <h4 class="font-semibold text-gray-700 mb-3">Müşteri Bilgileri</h4>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-500">Ad Soyad:</span>
                    <span class="font-medium">{{ $return->customer_name }}</span>
                </div>
                @if($return->customer_phone)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Telefon:</span>
                        <span class="font-medium">{{ $return->customer_phone }}</span>
                    </div>
                @endif
                @if($return->tracking_number)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Kargo Takip:</span>
                        <span class="font-medium">{{ $return->tracking_number }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-500">Tarih:</span>
                    <span class="font-medium">{{ $return->created_at->format('d.m.Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
    
    @if($return->notes)
        <div class="mb-6">
            <h4 class="font-semibold text-gray-700 mb-3">Notlar</h4>
            <p class="text-gray-600 bg-gray-50 p-3 rounded-lg">{{ $return->notes }}</p>
        </div>
    @endif
    
    <!-- Checklist'ler -->
    @php
        $allChecklists = $return->checklists->groupBy('stage');
    @endphp
    
    @if($allChecklists->count() > 0)
        <div>
            <h4 class="font-semibold text-gray-700 mb-3">İşlem Adımları</h4>
            <div class="space-y-4">
                @foreach($allChecklists as $stage => $checklists)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h5 class="font-medium text-gray-700 mb-2">{{ \App\Models\ReturnRequest::STATUSES[$stage] ?? $stage }}</h5>
                        <div class="space-y-2">
                            @foreach($checklists as $checklist)
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" 
                                           class="rounded text-blue-600"
                                           wire:click="toggleChecklist({{ $checklist->id }})"
                                           {{ $checklist->is_checked ? 'checked' : '' }}>
                                    <span class="{{ $checklist->is_checked ? 'line-through text-gray-400' : 'text-gray-700' }}">
                                        {{ $checklist->item_text }}
                                    </span>
                                    @if($checklist->is_checked && $checklist->checked_at)
                                        <span class="text-xs text-gray-500">
                                            ({{ $checklist->checked_at->format('d.m H:i') }} - {{ $checklist->checkedBy?->name }})
                                        </span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<div class="border-t p-4 bg-gray-50 rounded-b-2xl">
    <div class="flex justify-between items-center">
        <div class="text-sm text-gray-500">
            Son güncelleme: {{ $return->updated_at->diffForHumans() }}
        </div>
        <div class="flex space-x-2">
            <button onclick="closeReturnDetails()" 
                    class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
                Kapat
            </button>
            <a href="{{ route('filament.admin.resources.return-requests.edit', $return) }}" 
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                Düzenle
            </a>
        </div>
    </div>
</div>