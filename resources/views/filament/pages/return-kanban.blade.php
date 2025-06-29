@php
    use Illuminate\Support\Facades\Storage;
@endphp

<x-filament-panels::page>
    @push('styles')
    <style>
        /* Mobile-First Design */
        .mobile-kanban-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 16px;
            margin: -16px;
            margin-top: 0;
            min-height: 100vh;
        }
        
        /* Modern Checklist Styles */
        .checklist-modern-item {
            margin-bottom: 12px;
        }
        
        .checklist-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            position: relative;
            padding-left: 36px;
            user-select: none;
        }
        
        .checklist-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checklist-custom-box {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 24px;
            width: 24px;
            background-color: white;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .checklist-input:hover ~ .checklist-custom-box {
            border-color: #667eea;
            background-color: #667eea10;
        }
        
        .checklist-input:checked ~ .checklist-custom-box {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .checklist-tick {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 16px;
            height: 16px;
            fill: white;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .checklist-input:checked ~ .checklist-custom-box .checklist-tick {
            opacity: 1;
        }
        
        .checklist-text {
            font-size: 15px;
            color: #374151;
            line-height: 1.5;
            transition: all 0.3s ease;
        }
        
        .checklist-text.checked {
            text-decoration: line-through;
            color: #9ca3af;
        }
        
        .checklist-meta {
            font-size: 12px;
            color: #9ca3af;
            margin-left: 36px;
            margin-top: 4px;
        }
        
        /* Modern Modal Styles */
        .modal-modern-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 0;
            border-radius: 16px 16px 0 0;
            overflow: hidden;
        }
        
        .modal-header-content {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header-left {
            flex: 1;
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 800;
            color: #1f2937;
            margin: 0;
            line-height: 1.2;
        }
        
        .modal-subtitle {
            font-size: 16px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .modal-close-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #f3f4f6;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #6b7280;
        }
        
        .modal-close-btn:hover {
            background: #e5e7eb;
            color: #374151;
        }
        
        .modal-status-bar {
            height: 4px;
            width: 100%;
            background: linear-gradient(90deg, currentColor 0%, currentColor 100%);
        }
        
        .modal-modern-body {
            padding: 0;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .modal-section {
            padding: 24px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .modal-section:last-child {
            border-bottom: none;
        }
        
        .section-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 640px) {
            .section-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .info-card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }
        
        .info-card-title {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-card-title svg {
            color: #6b7280;
        }
        
        .info-list {
            space-y: 12px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .info-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .info-value {
            font-size: 14px;
            color: #1f2937;
            font-weight: 600;
            text-align: right;
        }
        
        .status-chip {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .modal-footer {
            background: #f9fafb;
            padding: 20px 24px;
            border-top: 1px solid #e5e7eb;
            border-radius: 0 0 16px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-footer-left {
            font-size: 14px;
            color: #6b7280;
        }
        
        .modal-footer-actions {
            display: flex;
            gap: 12px;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .modal-btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        
        .modal-btn-secondary:hover {
            background: #d1d5db;
        }
        
        .modal-btn-primary {
            background: #667eea;
            color: white;
        }
        
        .modal-btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .checklist-section {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
        }
        
        .checklist-stage-title {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Media Grid Styles */
        .media-item img {
            transition: all 0.3s ease;
        }
        
        .media-item:hover img {
            transform: scale(1.05);
        }
        
        /* Mobile Header - Stack Layout */
        .mobile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .mobile-title {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 20px;
            font-weight: 800;
            margin: 0 0 8px 0;
            text-align: center;
        }
        
        .mobile-subtitle {
            color: #6b7280;
            font-size: 14px;
            text-align: center;
            margin-bottom: 16px;
        }
        
        /* Mobile Stats Bar */
        .mobile-stats {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            background: rgba(255, 255, 255, 0.9);
            padding: 8px 16px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            display: block;
        }
        
        .stat-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Touch-Friendly Store Selector */
        .mobile-store-selector {
            width: 100%;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            font-weight: 500;
            color: #374151;
            transition: all 0.3s ease;
            -webkit-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
        }
        
        .mobile-store-selector:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        /* Mobile Kanban Tabs */
        .mobile-kanban-tabs {
            display: flex;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 16px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .mobile-tab {
            flex: 1;
            min-width: 80px;
            background: transparent;
            border: none;
            padding: 12px 8px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.3s ease;
            text-align: center;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .mobile-tab.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        
        .mobile-tab-icon {
            font-size: 16px;
            display: block;
            margin-bottom: 4px;
        }
        
        .mobile-tab-count {
            background: rgba(255, 255, 255, 0.3);
            color: currentColor;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
            margin-top: 4px;
            display: inline-block;
        }
        
        .mobile-tab.active .mobile-tab-count {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Mobile Cards Container */
        .mobile-cards-container {
            display: none;
            max-height: 70vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding-right: 4px;
        }
        
        .mobile-cards-container.active {
            display: block;
        }
        
        /* Mobile Return Cards */
        .mobile-return-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-left: 6px solid;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .mobile-return-card:active {
            transform: scale(0.98);
        }
        
        .card-pending { border-left-color: #eb5a46; }
        .card-in_transit { border-left-color: #f2d600; }
        .card-processing { border-left-color: #61bd4f; }
        .card-completed { border-left-color: #c377e0; }
        
        .mobile-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .mobile-card-id {
            font-size: 18px;
            font-weight: 800;
            color: #1f2937;
            line-height: 1;
        }
        
        .mobile-card-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-pending { background: #ffebe7; color: #c53030; }
        .badge-in_transit { background: #fffbeb; color: #92400e; }
        .badge-processing { background: #f0fdf4; color: #166534; }
        .badge-completed { background: #f3e8ff; color: #7c3aed; }
        
        .mobile-customer-info {
            margin-bottom: 16px;
        }
        
        .mobile-customer-name {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }
        
        .mobile-product-name {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.4;
            margin-bottom: 12px;
        }
        
        .mobile-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #f3f4f6;
        }
        
        .mobile-date {
            font-size: 12px;
            color: #9ca3af;
        }
        
        /* Checklist Progress */
        .checklist-progress {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }
        
        .checklist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .checklist-title {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .checklist-count {
            font-size: 12px;
            color: #6b7280;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #10b981;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        /* Empty State */
        .mobile-empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }
        
        .mobile-empty-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            opacity: 0.5;
        }
        
        .mobile-empty-text {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #6b7280;
        }
        
        .mobile-empty-subtext {
            font-size: 14px;
            opacity: 0.7;
            color: #9ca3af;
        }
        
        /* Desktop Adaptations */
        @media (min-width: 768px) {
            .mobile-kanban-container {
                padding: 24px;
                margin: -24px;
                margin-top: 0;
            }
            
            .mobile-header {
                padding: 24px;
            }
            
            .mobile-title {
                font-size: 28px;
            }
            
            .mobile-subtitle {
                font-size: 16px;
            }
            
            /* Desktop: Hide mobile tabs */
            .mobile-kanban-tabs {
                display: none !important;
            }
            
            /* Desktop Layout */
            .desktop-layout {
                display: flex;
                gap: 20px;
                margin-top: 20px;
            }
            
            /* Desktop Kanban Grid */
            .desktop-kanban-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 24px;
            }
            
            .desktop-column {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 16px;
                padding: 20px;
                min-height: 500px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            }
            
            .desktop-column-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 12px;
                border-bottom: 2px solid;
            }
            
            .desktop-column-pending .desktop-column-header { border-color: #eb5a46; }
            .desktop-column-in_transit .desktop-column-header { border-color: #f2d600; }
            .desktop-column-processing .desktop-column-header { border-color: #61bd4f; }
            .desktop-column-completed .desktop-column-header { border-color: #c377e0; }
            
            .desktop-column-title {
                font-weight: 600;
                font-size: 16px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .desktop-column-pending .desktop-column-title { color: #eb5a46; }
            .desktop-column-in_transit .desktop-column-title { color: #f2d600; }
            .desktop-column-processing .desktop-column-title { color: #61bd4f; }
            .desktop-column-completed .desktop-column-title { color: #c377e0; }
            
            .desktop-column-count {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }
            
            /* Desktop: Show all containers */
            .mobile-cards-container {
                display: block !important;
                max-height: none !important;
                padding-right: 0;
            }
            
            /* Desktop card hover effects */
            .mobile-return-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            }
        }
        
        /* Drag and Drop Styles */
        .dragging {
            opacity: 0.5;
        }
        
        .drag-over {
            background: rgba(102, 126, 234, 0.1);
            border: 2px dashed #667eea;
        }
        
        /* Custom Scrollbar */
        .mobile-cards-container::-webkit-scrollbar,
        .desktop-column::-webkit-scrollbar {
            width: 8px;
        }
        
        .mobile-cards-container::-webkit-scrollbar-track,
        .desktop-column::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .mobile-cards-container::-webkit-scrollbar-thumb,
        .desktop-column::-webkit-scrollbar-thumb {
            background-color: rgba(0,0,0,0.2);
            border-radius: 4px;
        }
        
        .mobile-cards-container::-webkit-scrollbar-thumb:hover,
        .desktop-column::-webkit-scrollbar-thumb:hover {
            background-color: rgba(0,0,0,0.3);
        }
    </style>
    @endpush

    @php
        $stages = [
            'pending' => ['title' => 'Beklemede', 'icon' => 'heroicon-o-clock', 'color' => '#eb5a46'],
            'in_transit' => ['title' => 'Yolda', 'icon' => 'heroicon-o-truck', 'color' => '#f2d600'],
            'processing' => ['title' => 'İşlemde', 'icon' => 'heroicon-o-cog-6-tooth', 'color' => '#61bd4f'],
            'completed' => ['title' => 'Tamamlandı', 'icon' => 'heroicon-o-check-circle', 'color' => '#c377e0'],
        ];
        
        $totalCount = 0;
        foreach ($stages as $key => $stage) {
            $totalCount += $returns->get($key, collect())->count();
        }
    @endphp

    <div class="mobile-kanban-container">
        <!-- Mobile-First Header -->
        <div class="mobile-header">
            <h1 class="mobile-title">İade Takip Sistemi</h1>
            <p class="mobile-subtitle">Müşteri iade taleplerini takip edin</p>
            
            <!-- Mobile Stats -->
            <div class="mobile-stats">
                <div class="stat-item">
                    <span class="stat-number">{{ $totalCount }}</span>
                    <span class="stat-label">Toplam</span>
                </div>
                @foreach($stages as $key => $stage)
                    <div class="stat-item">
                        <span class="stat-number">{{ $returns->get($key, collect())->count() }}</span>
                        <span class="stat-label">{{ $stage['title'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Mobile Tab Navigation -->
        <div class="mobile-kanban-tabs">
            @foreach($stages as $key => $stage)
                <button class="mobile-tab {{ $loop->first ? 'active' : '' }}" onclick="switchTab('{{ $key }}')">
                    @svg($stage['icon'], 'w-4 h-4 mobile-tab-icon')
                    <div>{{ $stage['title'] }}</div>
                    <span class="mobile-tab-count">{{ $returns->get($key, collect())->count() }}</span>
                </button>
            @endforeach
        </div>

        <!-- Mobile Tab Content -->
        <div class="block md:hidden">
            @foreach($stages as $stageKey => $stage)
                <div id="{{ $stageKey }}" class="mobile-cards-container {{ $loop->first ? 'active' : '' }}">
                    @forelse($returns->get($stageKey, []) as $return)
                        <div class="mobile-return-card card-{{ $stageKey }}" 
                             wire:click="loadReturnDetails({{ $return->id }})"
                             data-return-id="{{ $return->id }}">
                            <div class="mobile-card-header">
                                <div class="mobile-card-id">#{{ $return->order_number }}</div>
                                <div class="mobile-card-badge badge-{{ $stageKey }}">{{ $stage['title'] }}</div>
                            </div>
                            
                            <div class="mobile-customer-info">
                                <div class="mobile-customer-name">{{ $return->customer_name }}</div>
                                <div class="mobile-product-name">{{ $return->product_name }}</div>
                            </div>
                            
                            @php
                                $stageChecklists = $return->checklists->where('stage', $stageKey);
                                $total = $stageChecklists->count();
                                $checked = $stageChecklists->where('is_checked', true)->count();
                                $percentage = $total > 0 ? round(($checked / $total) * 100) : 0;
                            @endphp
                            
                            @if($total > 0)
                                <div class="checklist-progress">
                                    <div class="checklist-header">
                                        <div class="checklist-title">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                            Checklist
                                        </div>
                                        <div class="checklist-count">{{ $checked }}/{{ $total }}</div>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="mobile-card-footer">
                                <div class="mobile-date">{{ $return->created_at->format('d.m.Y') }}</div>
                                @if($return->resolution)
                                    <span class="badge-{{ $return->resolution }}">
                                        {{ \App\Models\ReturnRequest::RESOLUTIONS[$return->resolution] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="mobile-empty-state">
                            <svg class="mobile-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"/>
                            </svg>
                            <div class="mobile-empty-text">{{ $stage['title'] }} iade yok</div>
                            <div class="mobile-empty-subtext">Bu aşamada iade bulunmuyor</div>
                        </div>
                    @endforelse
                </div>
            @endforeach
        </div>

        <!-- Desktop Layout -->
        <div class="hidden md:block desktop-layout">
            <div class="desktop-kanban-grid">
                @foreach($stages as $stageKey => $stage)
                    <div class="desktop-column desktop-column-{{ $stageKey }}" 
                         data-stage="{{ $stageKey }}"
                         ondrop="drop(event)"
                         ondragover="allowDrop(event)">
                        <div class="desktop-column-header">
                            <div class="desktop-column-title">
                                @svg($stage['icon'], 'w-5 h-5')
                                <span>{{ $stage['title'] }}</span>
                            </div>
                            <div class="desktop-column-count">{{ $returns->get($stageKey, collect())->count() }}</div>
                        </div>
                        
                        <div class="space-y-3">
                            @forelse($returns->get($stageKey, []) as $return)
                                <div class="mobile-return-card card-{{ $stageKey }}" 
                                     wire:click="loadReturnDetails({{ $return->id }})"
                                     draggable="true"
                                     ondragstart="drag(event)"
                                     data-return-id="{{ $return->id }}">
                                    <div class="mobile-card-header">
                                        <div class="mobile-card-id">#{{ $return->order_number }}</div>
                                        <div class="mobile-card-badge badge-{{ $stageKey }}">{{ $stage['title'] }}</div>
                                    </div>
                                    
                                    <div class="mobile-customer-info">
                                        <div class="mobile-customer-name">{{ $return->customer_name }}</div>
                                        <div class="mobile-product-name">{{ $return->product_name }}</div>
                                    </div>
                                    
                                    @php
                                        $stageChecklists = $return->checklists->where('stage', $stageKey);
                                        $total = $stageChecklists->count();
                                        $checked = $stageChecklists->where('is_checked', true)->count();
                                        $percentage = $total > 0 ? round(($checked / $total) * 100) : 0;
                                    @endphp
                                    
                                    @if($total > 0)
                                        <div class="checklist-progress">
                                            <div class="checklist-header">
                                                <div class="checklist-title">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                    </svg>
                                                    Checklist
                                                </div>
                                                <div class="checklist-count">{{ $checked }}/{{ $total }}</div>
                                            </div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: {{ $percentage }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <div class="mobile-card-footer">
                                        <div class="mobile-date">{{ $return->created_at->format('d.m.Y') }}</div>
                                        @if($return->resolution)
                                            <span class="badge-{{ $return->resolution }}">
                                                {{ \App\Models\ReturnRequest::RESOLUTIONS[$return->resolution] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="mobile-empty-state">
                                    <svg class="mobile-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"/>
                                    </svg>
                                    <div class="mobile-empty-text">{{ $stage['title'] }} iade yok</div>
                                    <div class="mobile-empty-subtext">Bu aşamada iade bulunmuyor</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Return Details Modal -->
        @if($showModal && $selectedReturn)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" 
                         wire:click="closeModal"></div>
                    
                    <div class="relative bg-white rounded-2xl max-w-2xl w-full shadow-xl transform transition-all">
                        <div class="modal-modern-header">
                            <div class="modal-header-content">
                                <div class="modal-header-left">
                                    <h3 class="modal-title">#{{ $selectedReturn->order_number }}</h3>
                                    <p class="modal-subtitle">{{ $selectedReturn->customer_name }}</p>
                                </div>
                                <button wire:click="closeModal" class="modal-close-btn">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="modal-status-bar" style="background: {{ \App\Models\ReturnRequest::STATUSES[$selectedReturn->status] === 'Beklemede' ? '#eb5a46' : (\App\Models\ReturnRequest::STATUSES[$selectedReturn->status] === 'Yolda' ? '#f2d600' : (\App\Models\ReturnRequest::STATUSES[$selectedReturn->status] === 'İşlemde' ? '#61bd4f' : '#c377e0')) }}"></div>
                        </div>

                        <div class="modal-modern-body">
                            <!-- İade Detayları -->
                            <div class="modal-section">
                                <div class="section-grid">
                                    <div class="info-card">
                                        <h4 class="info-card-title">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                            </svg>
                                            İade Bilgileri
                                        </h4>
                                        <div class="info-list">
                                            <div class="info-row">
                                                <span class="info-label">Ürün:</span>
                                                <span class="info-value">{{ $selectedReturn->product_name }}</span>
                                            </div>
                                            @if($selectedReturn->product_sku)
                                                <div class="info-row">
                                                    <span class="info-label">SKU:</span>
                                                    <span class="info-value">{{ $selectedReturn->product_sku }}</span>
                                                </div>
                                            @endif
                                            <div class="info-row">
                                                <span class="info-label">Adet:</span>
                                                <span class="info-value">{{ $selectedReturn->quantity ?? 1 }}</span>
                                            </div>
                                            @if($selectedReturn->refund_amount)
                                                <div class="info-row">
                                                    <span class="info-label">İade Tutarı:</span>
                                                    <span class="info-value">{{ $selectedReturn->currency }} {{ number_format($selectedReturn->refund_amount, 2) }}</span>
                                                </div>
                                            @endif
                                            <div class="info-row">
                                                <span class="info-label">İade Nedeni:</span>
                                                <span class="info-value">{{ $selectedReturn->return_reason }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Durum:</span>
                                                <span class="info-value">
                                                    <span class="status-chip" style="background: {{ \App\Models\ReturnRequest::STATUSES[$selectedReturn->status] === 'Beklemede' ? '#eb5a4620' : (\App\Models\ReturnRequest::STATUSES[$selectedReturn->status] === 'Yolda' ? '#f2d60020' : (\App\Models\ReturnRequest::STATUSES[$selectedReturn->status] === 'İşlemde' ? '#61bd4f20' : '#c377e020')) }}; color: {{ \App\Models\ReturnRequest::STATUSES[$selectedReturn->status] === 'Beklemede' ? '#eb5a46' : (\App\Models\ReturnRequest::STATUSES[$selectedReturn->status] === 'Yolda' ? '#f2d600' : (\App\Models\ReturnRequest::STATUSES[$selectedReturn->status] === 'İşlemde' ? '#61bd4f' : '#c377e0')) }}">
                                                        {{ \App\Models\ReturnRequest::STATUSES[$selectedReturn->status] ?? $selectedReturn->status }}
                                                    </span>
                                                </span>
                                            </div>
                                            @if($selectedReturn->resolution)
                                                <div class="info-row">
                                                    <span class="info-label">Çözüm:</span>
                                                    <span class="info-value">{{ \App\Models\ReturnRequest::RESOLUTIONS[$selectedReturn->resolution] ?? $selectedReturn->resolution }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="info-card">
                                        <h4 class="info-card-title">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            Müşteri Bilgileri
                                        </h4>
                                        <div class="info-list">
                                            <div class="info-row">
                                                <span class="info-label">Ad Soyad:</span>
                                                <span class="info-value">{{ $selectedReturn->customer_name }}</span>
                                            </div>
                                            @if($selectedReturn->customer_phone)
                                                <div class="info-row">
                                                    <span class="info-label">Telefon:</span>
                                                    <span class="info-value">{{ $selectedReturn->customer_phone }}</span>
                                                </div>
                                            @endif
                                            @if($selectedReturn->tracking_number)
                                                <div class="info-row">
                                                    <span class="info-label">Bizim Kargo:</span>
                                                    <span class="info-value">{{ $selectedReturn->tracking_number }}</span>
                                                </div>
                                            @endif
                                            @if($selectedReturn->customer_tracking_number)
                                                <div class="info-row">
                                                    <span class="info-label">Müşteri Kargosu:</span>
                                                    <span class="info-value">{{ $selectedReturn->customer_tracking_number }}</span>
                                                </div>
                                            @endif
                                            <div class="info-row">
                                                <span class="info-label">Tarih:</span>
                                                <span class="info-value">{{ $selectedReturn->created_at->format('d.m.Y H:i') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @if($selectedReturn->notes)
                                <div class="modal-section">
                                    <div class="info-card" style="width: 100%;">
                                        <h4 class="info-card-title">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Notlar
                                        </h4>
                                        <p style="line-height: 1.6; color: #4b5563;">{{ $selectedReturn->notes }}</p>
                                    </div>
                                </div>
                            @endif
                            
                            @if($selectedReturn->media && count($selectedReturn->media) > 0)
                                <div class="modal-section">
                                    <h4 class="info-card-title" style="margin-bottom: 20px;">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Ekli Dosyalar
                                    </h4>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                        @foreach($selectedReturn->media as $media)
                                            <div class="media-item">
                                                @if(str_contains($media, '.pdf'))
                                                    <a href="{{ Storage::url($media) }}" target="_blank" class="block p-4 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                                        <svg class="w-12 h-12 mx-auto text-red-600 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10,13V19H12V13H10M14,13V19H16V13H14Z"/>
                                                        </svg>
                                                        <p class="text-xs text-gray-600 text-center truncate">{{ basename($media) }}</p>
                                                    </a>
                                                @else
                                                    <a href="{{ Storage::url($media) }}" target="_blank" class="block">
                                                        <img src="{{ Storage::url($media) }}" alt="İade görseli" class="w-full h-32 object-cover rounded-lg hover:opacity-90 transition">
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Checklist'ler -->
                            @php
                                $allChecklists = $selectedReturn->checklists->groupBy('stage');
                            @endphp
                            
                            @if($allChecklists->count() > 0)
                                <div class="modal-section">
                                    <h4 class="info-card-title" style="margin-bottom: 20px;">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                        </svg>
                                        İşlem Adımları
                                    </h4>
                                    <div class="space-y-4">
                                        @foreach($allChecklists as $stage => $checklists)
                                            <div class="checklist-section">
                                                <h5 class="checklist-stage-title">
                                                    @php
                                                        $stageIcons = [
                                                            'pending' => 'heroicon-o-clock',
                                                            'in_transit' => 'heroicon-o-truck',
                                                            'processing' => 'heroicon-o-cog-6-tooth',
                                                            'completed' => 'heroicon-o-check-circle'
                                                        ];
                                                        $stageColors = [
                                                            'pending' => '#eb5a46',
                                                            'in_transit' => '#f2d600',
                                                            'processing' => '#61bd4f',
                                                            'completed' => '#c377e0'
                                                        ];
                                                    @endphp
                                                    <span style="color: {{ $stageColors[$stage] ?? '#6b7280' }}">
                                                        @svg($stageIcons[$stage] ?? 'heroicon-o-folder', 'w-5 h-5')
                                                    </span>
                                                    {{ \App\Models\ReturnRequest::STATUSES[$stage] ?? $stage }}
                                                </h5>
                                                <div class="space-y-2">
                                                    @foreach($checklists as $checklist)
                                                        <div class="checklist-modern-item">
                                                            <label class="checklist-label">
                                                                <input type="checkbox" 
                                                                       class="checklist-input"
                                                                       wire:click="toggleChecklist({{ $checklist->id }})"
                                                                       {{ $checklist->is_checked ? 'checked' : '' }}>
                                                                <span class="checklist-custom-box">
                                                                    <svg class="checklist-tick" viewBox="0 0 24 24">
                                                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                                                    </svg>
                                                                </span>
                                                                <span class="checklist-text {{ $checklist->is_checked ? 'checked' : '' }}">
                                                                    {{ $checklist->item_text }}
                                                                </span>
                                                            </label>
                                                            @if($checklist->is_checked && $checklist->checked_at)
                                                                <div class="checklist-meta">
                                                                    {{ $checklist->checked_at->format('d.m H:i') }} - {{ $checklist->checkedBy?->name ?? 'Sistem' }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="modal-footer">
                            <div class="modal-footer-left">
                                Son güncelleme: {{ $selectedReturn->updated_at->diffForHumans() }}
                            </div>
                            <div class="modal-footer-actions">
                                <button wire:click="closeModal" class="modal-btn modal-btn-secondary">
                                    Kapat
                                </button>
                                <a href="{{ route('filament.admin.resources.return-requests.edit', $selectedReturn) }}" 
                                   class="modal-btn modal-btn-primary">
                                    Düzenle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        // Mobile tab switching
        function switchTab(tabName) {
            if (window.innerWidth >= 768) return;
            
            document.querySelectorAll('.mobile-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.mobile-cards-container').forEach(container => {
                container.classList.remove('active');
            });
            
            event.target.closest('.mobile-tab').classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }
        
        // Drag and Drop for Desktop
        function allowDrop(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.add('drag-over');
        }
        
        function drag(ev) {
            ev.dataTransfer.setData("returnId", ev.target.dataset.returnId);
            ev.target.classList.add('dragging');
        }
        
        function drop(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.remove('drag-over');
            
            const returnId = ev.dataTransfer.getData("returnId");
            const newStage = ev.currentTarget.dataset.stage;
            
            // Remove dragging class
            document.querySelector('.dragging').classList.remove('dragging');
            
            // Update via Livewire
            @this.moveCard(returnId, newStage);
        }
        
        // Handle drag leave
        document.addEventListener('dragleave', function(e) {
            if (e.target.classList.contains('desktop-column')) {
                e.target.classList.remove('drag-over');
            }
        });
    </script>
    @endpush
</x-filament-panels::page>