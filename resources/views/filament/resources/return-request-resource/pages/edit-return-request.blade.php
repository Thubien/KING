@php
    $record = $this->getRecord();
    $stages = [
        'pending' => ['title' => 'Beklemede', 'color' => '#eb5a46', 'icon' => 'heroicon-o-clock'],
        'in_transit' => ['title' => 'Yolda', 'color' => '#f2d600', 'icon' => 'heroicon-o-truck'],
        'processing' => ['title' => 'İşlemde', 'color' => '#61bd4f', 'icon' => 'heroicon-o-cog-6-tooth'],
        'completed' => ['title' => 'Tamamlandı', 'color' => '#c377e0', 'icon' => 'heroicon-o-check-circle'],
    ];
    $currentStage = $stages[$record->status] ?? $stages['pending'];
@endphp

<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
    @push('styles')
    <style>
        .return-edit-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 24px;
            padding: 24px;
            margin: -24px;
            min-height: 100vh;
        }
        
        .custom-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .card-header {
            background: linear-gradient(135deg, {{ $currentStage['color'] }}20, {{ $currentStage['color'] }}10);
            border-bottom: 2px solid {{ $currentStage['color'] }}30;
            padding: 20px 24px;
        }
        
        .card-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .card-header p {
            color: #6b7280;
            font-size: 14px;
            margin: 4px 0 0 0;
        }
        
        .card-body {
            padding: 24px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            background: #f9fafb;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .info-item:hover {
            border-color: {{ $currentStage['color'] }};
            background: {{ $currentStage['color'] }}05;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .info-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 500;
            color: #1f2937;
            line-height: 1.5;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            background: {{ $currentStage['color'] }}20;
            color: {{ $currentStage['color'] }};
            border: 2px solid {{ $currentStage['color'] }}30;
        }
        
        .checklist-section {
            background: #f9fafb;
            border-radius: 16px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .checklist-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: white;
            border-radius: 12px;
            margin-bottom: 12px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .checklist-item:hover {
            border-color: {{ $currentStage['color'] }}40;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .checklist-item:last-child {
            margin-bottom: 0;
        }
        
        .checklist-checkbox {
            width: 24px;
            height: 24px;
            border-radius: 8px;
            border: 2px solid #d1d5db;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            flex-shrink: 0;
        }
        
        .checklist-checkbox.checked {
            background: {{ $currentStage['color'] }};
            border-color: {{ $currentStage['color'] }};
        }
        
        .checklist-checkbox.checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 16px;
            font-weight: bold;
        }
        
        .checklist-text {
            flex: 1;
            font-size: 15px;
            color: #374151;
            transition: all 0.3s ease;
        }
        
        .checklist-text.completed {
            text-decoration: line-through;
            color: #9ca3af;
        }
        
        .checklist-info {
            font-size: 12px;
            color: #9ca3af;
            white-space: nowrap;
        }
        
        .timeline-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 32px 24px;
            background: white;
            border-radius: 20px;
            margin-bottom: 24px;
            position: relative;
        }
        
        .timeline-item {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .timeline-icon {
            width: 64px;
            height: 64px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            border: 3px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .timeline-item.active .timeline-icon {
            background: {{ $currentStage['color'] }}20;
            border-color: {{ $currentStage['color'] }};
            transform: scale(1.1);
        }
        
        .timeline-item.completed .timeline-icon {
            background: #10b98120;
            border-color: #10b981;
        }
        
        .timeline-icon svg {
            width: 28px;
            height: 28px;
            color: #6b7280;
        }
        
        .timeline-item.active .timeline-icon svg {
            color: {{ $currentStage['color'] }};
        }
        
        .timeline-item.completed .timeline-icon svg {
            color: #10b981;
        }
        
        .timeline-title {
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 4px;
        }
        
        .timeline-item.active .timeline-title {
            color: {{ $currentStage['color'] }};
        }
        
        .timeline-item.completed .timeline-title {
            color: #10b981;
        }
        
        .timeline-date {
            font-size: 12px;
            color: #9ca3af;
        }
        
        .timeline-line {
            position: absolute;
            top: 48px;
            left: 50%;
            right: 50%;
            height: 3px;
            background: #e5e7eb;
            z-index: 1;
        }
        
        .timeline-item:first-child .timeline-line {
            left: 50%;
        }
        
        .timeline-item:last-child .timeline-line {
            right: 50%;
        }
        
        .timeline-item.completed + .timeline-item .timeline-line {
            background: #10b981;
        }
        
        /* Form özelleştirmeleri */
        .fi-page-edit-record .fi-fo-field-wrp {
            margin-bottom: 20px !important;
        }
        
        .fi-page-edit-record .fi-fo-field-wrp-label {
            font-weight: 600 !important;
            color: #374151 !important;
            margin-bottom: 8px !important;
            display: block !important;
        }
        
        /* Select dropdown styling */
        .fi-page-edit-record .fi-select-input {
            background-color: white !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 12px !important;
            padding: 12px 16px !important;
            transition: all 0.3s ease !important;
            width: 100% !important;
        }
        
        .fi-page-edit-record .fi-select-input:hover:not(:disabled) {
            border-color: {{ $currentStage['color'] }} !important;
            background-color: {{ $currentStage['color'] }}05 !important;
        }
        
        .fi-page-edit-record .fi-select-input:focus {
            border-color: {{ $currentStage['color'] }} !important;
            box-shadow: 0 0 0 3px {{ $currentStage['color'] }}20 !important;
            outline: none !important;
        }
        
        /* Input fields styling */
        .fi-page-edit-record .fi-input {
            background-color: white !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 12px !important;
            padding: 12px 16px !important;
            transition: all 0.3s ease !important;
            font-size: 15px !important;
        }
        
        .fi-page-edit-record .fi-input:hover:not(:disabled) {
            border-color: {{ $currentStage['color'] }} !important;
            background-color: {{ $currentStage['color'] }}05 !important;
        }
        
        .fi-page-edit-record .fi-input:focus {
            border-color: {{ $currentStage['color'] }} !important;
            box-shadow: 0 0 0 3px {{ $currentStage['color'] }}20 !important;
            outline: none !important;
        }
        
        /* Disabled inputs */
        .fi-page-edit-record .fi-input:disabled {
            background: #f9fafb !important;
            color: #6b7280 !important;
            cursor: not-allowed !important;
            opacity: 0.7 !important;
        }
        
        /* Textarea özelleştirme */
        .fi-page-edit-record textarea.fi-textarea-input {
            min-height: 120px !important;
            resize: vertical !important;
            background-color: white !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 12px !important;
            padding: 12px 16px !important;
            transition: all 0.3s ease !important;
        }
        
        .fi-page-edit-record textarea.fi-textarea-input:hover:not(:disabled) {
            border-color: {{ $currentStage['color'] }} !important;
            background-color: {{ $currentStage['color'] }}05 !important;
        }
        
        .fi-page-edit-record textarea.fi-textarea-input:focus {
            border-color: {{ $currentStage['color'] }} !important;
            box-shadow: 0 0 0 3px {{ $currentStage['color'] }}20 !important;
            outline: none !important;
        }
        
        /* Form Actions Styling */
        .fi-page-edit-record .fi-form-actions {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            border-radius: 20px !important;
            padding: 24px !important;
            margin-top: 24px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1) !important;
        }
        
        .fi-page-edit-record .fi-btn {
            border-radius: 12px !important;
            padding: 12px 28px !important;
            font-weight: 600 !important;
            font-size: 15px !important;
            transition: all 0.3s ease !important;
            text-transform: none !important;
        }
        
        .fi-page-edit-record .fi-btn-color-primary {
            background: linear-gradient(135deg, {{ $currentStage['color'] }}, {{ $currentStage['color'] }}dd) !important;
            border: none !important;
            color: white !important;
        }
        
        .fi-page-edit-record .fi-btn-color-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px {{ $currentStage['color'] }}40 !important;
        }
        
        .fi-page-edit-record .fi-btn-color-danger {
            background: #ef4444 !important;
            border: none !important;
            color: white !important;
        }
        
        .fi-page-edit-record .fi-btn-color-danger:hover {
            background: #dc2626 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3) !important;
        }
        
        /* Media Grid Styles */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 16px;
        }
        
        .media-item-edit {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .media-item-edit:hover {
            border-color: {{ $currentStage['color'] }};
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .media-image {
            display: block;
            width: 100%;
            height: 150px;
            overflow: hidden;
        }
        
        .media-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .media-item-edit:hover .media-image img {
            transform: scale(1.1);
        }
        
        .media-pdf {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            height: 150px;
            text-decoration: none;
            color: #ef4444;
            transition: all 0.3s ease;
        }
        
        .media-pdf:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .media-pdf p {
            margin-top: 8px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
            word-break: break-all;
        }
    </style>
    @endpush
    
    <div class="return-edit-container">
        <!-- Timeline -->
        <div class="timeline-section">
            @foreach($stages as $key => $stage)
                <div class="timeline-item {{ $record->status === $key ? 'active' : '' }} {{ array_search($key, array_keys($stages)) < array_search($record->status, array_keys($stages)) ? 'completed' : '' }}">
                    @if(!$loop->first)
                        <div class="timeline-line"></div>
                    @endif
                    <div class="timeline-icon">
                        @svg($stage['icon'], 'w-7 h-7')
                    </div>
                    <div class="timeline-title">{{ $stage['title'] }}</div>
                    @if($record->status === $key)
                        <div class="timeline-date">{{ $record->updated_at->format('d.m.Y') }}</div>
                    @endif
                </div>
            @endforeach
        </div>
        
        <!-- Header Card -->
        <div class="custom-card">
            <div class="card-header">
                <h3>
                    @svg($currentStage['icon'], 'w-6 h-6')
                    İade #{{ $record->order_number }}
                </h3>
                <p>{{ $record->customer_name }} - {{ $record->product_name }}</p>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Mağaza</div>
                        <div class="info-value">{{ $record->store?->name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Durum</div>
                        <div class="info-value">
                            <span class="status-badge">
                                @svg($currentStage['icon'], 'w-4 h-4')
                                {{ $currentStage['title'] }}
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Talep Tarihi</div>
                        <div class="info-value">{{ $record->created_at->format('d.m.Y H:i') }}</div>
                    </div>
                    @if($record->refund_amount)
                        <div class="info-item">
                            <div class="info-label">İade Tutarı</div>
                            <div class="info-value" style="font-size: 18px; color: {{ $currentStage['color'] }}; font-weight: 700;">
                                {{ $record->currency }} {{ number_format($record->refund_amount, 2) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Customer Info Card -->
        <div class="custom-card">
            <div class="card-header">
                <h3>
                    @svg('heroicon-o-user', 'w-6 h-6')
                    Müşteri Bilgileri
                </h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Ad Soyad</div>
                        <div class="info-value">{{ $record->customer_name }}</div>
                    </div>
                    @if($record->customer_phone)
                        <div class="info-item">
                            <div class="info-label">Telefon</div>
                            <div class="info-value">{{ $record->customer_phone }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Product Info Card -->
        <div class="custom-card">
            <div class="card-header">
                <h3>
                    @svg('heroicon-o-shopping-bag', 'w-6 h-6')
                    Ürün ve İade Detayları
                </h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Ürün Adı</div>
                        <div class="info-value">{{ $record->product_name }}</div>
                    </div>
                    @if($record->product_sku)
                        <div class="info-item">
                            <div class="info-label">SKU</div>
                            <div class="info-value">{{ $record->product_sku }}</div>
                        </div>
                    @endif
                    <div class="info-item">
                        <div class="info-label">Adet</div>
                        <div class="info-value">{{ $record->quantity ?? 1 }}</div>
                    </div>
                    @if($record->tracking_number)
                        <div class="info-item">
                            <div class="info-label">Bizim Kargo</div>
                            <div class="info-value">{{ $record->tracking_number }}</div>
                        </div>
                    @endif
                    @if($record->customer_tracking_number)
                        <div class="info-item">
                            <div class="info-label">Müşteri Kargosu</div>
                            <div class="info-value">{{ $record->customer_tracking_number }}</div>
                        </div>
                    @endif
                </div>
                <div class="info-item" style="margin-top: 20px;">
                    <div class="info-label">İade Nedeni</div>
                    <div class="info-value">{{ $record->return_reason }}</div>
                </div>
                @if($record->notes)
                    <div class="info-item" style="margin-top: 20px;">
                        <div class="info-label">Notlar</div>
                        <div class="info-value">{{ $record->notes }}</div>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Media Card -->
        @if($record->media && count($record->media) > 0)
            <div class="custom-card">
                <div class="card-header">
                    <h3>
                        @svg('heroicon-o-photo', 'w-6 h-6')
                        Ekli Dosyalar
                    </h3>
                    <p>İade ile ilgili görsel ve belgeler</p>
                </div>
                <div class="card-body">
                    <div class="media-grid">
                        @foreach($record->media as $media)
                            <div class="media-item-edit">
                                @if(str_contains($media, '.pdf'))
                                    <a href="{{ Storage::url($media) }}" target="_blank" class="media-pdf">
                                        <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10,13V19H12V13H10M14,13V19H16V13H14Z"/>
                                        </svg>
                                        <p>{{ basename($media) }}</p>
                                    </a>
                                @else
                                    <a href="{{ Storage::url($media) }}" target="_blank" class="media-image">
                                        <img src="{{ Storage::url($media) }}" alt="İade görseli">
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Checklist Card -->
        @php
            $checklists = $record->checklists()->where('stage', $record->status)->get();
        @endphp
        @if($checklists->isNotEmpty())
            <div class="custom-card">
                <div class="card-header">
                    <h3>
                        @svg('heroicon-o-clipboard-document-check', 'w-6 h-6')
                        İşlem Kontrol Listesi
                    </h3>
                    <p>{{ $currentStage['title'] }} aşaması için gerekli işlemler</p>
                </div>
                <div class="card-body">
                    <div class="checklist-section">
                        @foreach($checklists as $checklist)
                            <div class="checklist-item">
                                <div class="checklist-checkbox {{ $checklist->is_checked ? 'checked' : '' }}" 
                                     wire:click="$dispatch('checklist-toggle', { id: {{ $checklist->id }} })"></div>
                                <div class="checklist-text {{ $checklist->is_checked ? 'completed' : '' }}">
                                    {{ $checklist->item_text }}
                                </div>
                                @if($checklist->is_checked && $checklist->checked_at)
                                    <div class="checklist-info">
                                        {{ $checklist->checked_at->format('d.m H:i') }} - {{ $checklist->checkedBy?->name ?? 'Sistem' }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Form Section -->
        <div class="custom-card">
            <div class="card-header">
                <h3>
                    @svg('heroicon-o-pencil-square', 'w-6 h-6')
                    İşlem Güncelleme
                </h3>
                <p>Durum ve çözüm bilgilerini güncelleyin</p>
            </div>
            <div class="card-body">
                {{ $this->form }}
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        // Checklist toggle işlemi için event listener
        window.addEventListener('checklist-toggle', event => {
            // Bu event Livewire component'inde handle edilecek
            @this.call('toggleChecklist', event.detail.id);
        });
    </script>
    @endpush
    
        <x-filament-panels::form.actions 
            :actions="$this->getCachedFormActions()" 
            :full-width="$this->hasFullWidthFormActions()" 
        />
    </x-filament-panels::form>
</x-filament-panels::page>