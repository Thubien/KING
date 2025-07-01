<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerTimelineEvent;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\HtmlString;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('add_tag')
                ->label('Etiket Ekle')
                ->icon('heroicon-m-tag')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Select::make('tag')
                        ->label('Etiket')
                        ->options([
                            'vip' => 'VIP',
                            'wholesale' => 'Toptan',
                            'problematic' => 'Sorunlu',
                            'returning' => 'Sürekli İade',
                            'loyal' => 'Sadık',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->addTag($data['tag']);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Etiket eklendi')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('send_message')
                ->label('Mesaj Gönder')
                ->icon('heroicon-m-chat-bubble-left-ellipsis')
                ->color('info')
                ->modalHeading('Müşteriye Mesaj Gönder')
                ->form([
                    \Filament\Forms\Components\Select::make('channel')
                        ->label('İletişim Kanalı')
                        ->options([
                            'whatsapp' => 'WhatsApp',
                            'sms' => 'SMS',
                            'email' => 'E-posta',
                        ])
                        ->default(fn () => $this->record->preferred_contact_method ?? 'whatsapp')
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('message')
                        ->label('Mesaj')
                        ->rows(4)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    // TODO: Implement message sending
                    \Filament\Notifications\Notification::make()
                        ->title('Mesaj gönderildi')
                        ->body("Mesaj {$data['channel']} üzerinden gönderildi.")
                        ->success()
                        ->send();
                    
                    // Log timeline event
                    $this->record->logTimelineEvent(
                        'message_sent',
                        'Mesaj gönderildi',
                        "{$data['channel']} üzerinden mesaj gönderildi",
                        ['channel' => $data['channel'], 'message' => $data['message']]
                    );
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Müşteri Özeti')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Ad Soyad')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                                TextEntry::make('segment')
                                    ->label('Segment')
                                    ->getStateUsing(fn () => $this->record->getSegment())
                                    ->badge()
                                    ->color(fn (string $state): string => match($state) {
                                        'VIP' => 'warning',
                                        'Sadık Müşteri' => 'success',
                                        'Risk Altında' => 'danger',
                                        'Kayıp Müşteri' => 'gray',
                                        'Yeni Müşteri' => 'info',
                                        default => 'primary',
                                    }),
                                TextEntry::make('lifetime_value')
                                    ->label('Yaşam Boyu Değer')
                                    ->getStateUsing(fn () => number_format($this->record->total_spent, 2) . ' ' . strtoupper($this->record->store->currency ?? 'TRY'))
                                    ->weight(FontWeight::Bold)
                                    ->color('success'),
                                TextEntry::make('status')
                                    ->label('Durum')
                                    ->badge()
                                    ->color(fn (string $state): string => match($state) {
                                        'active' => 'success',
                                        'inactive' => 'warning',
                                        'blacklist' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'active' => 'Aktif',
                                        'inactive' => 'İnaktif',
                                        'blacklist' => 'Kara Liste',
                                        default => $state,
                                    }),
                            ]),
                    ]),
                
                Section::make('İletişim Bilgileri')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('email')
                                    ->label('E-posta')
                                    ->icon('heroicon-m-envelope')
                                    ->iconPosition(IconPosition::Before)
                                    ->copyable()
                                    ->default('—'),
                                TextEntry::make('phone')
                                    ->label('Telefon')
                                    ->icon('heroicon-m-phone')
                                    ->iconPosition(IconPosition::Before)
                                    ->copyable()
                                    ->default('—'),
                                TextEntry::make('whatsapp_number')
                                    ->label('WhatsApp')
                                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                                    ->iconPosition(IconPosition::Before)
                                    ->copyable()
                                    ->default('—'),
                                TextEntry::make('preferred_contact_method')
                                    ->label('Tercih Edilen İletişim')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(fn (?string $state): string => match($state) {
                                        'phone' => 'Telefon',
                                        'whatsapp' => 'WhatsApp',
                                        'email' => 'E-posta',
                                        'sms' => 'SMS',
                                        default => '—',
                                    }),
                            ]),
                    ])
                    ->collapsible(),
                
                Section::make('Satın Alma İstatistikleri')
                    ->schema([
                        Grid::make(6)
                            ->schema([
                                TextEntry::make('total_orders')
                                    ->label('Toplam Sipariş')
                                    ->numeric()
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-m-shopping-bag')
                                    ->iconPosition(IconPosition::Before)
                                    ->color('primary'),
                                TextEntry::make('total_spent')
                                    ->label('Toplam Harcama')
                                    ->money(fn () => strtolower($this->record->store->currency ?? 'try'))
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-m-currency-dollar')
                                    ->iconPosition(IconPosition::Before)
                                    ->color('success'),
                                TextEntry::make('avg_order_value')
                                    ->label('Ortalama Sepet')
                                    ->money(fn () => strtolower($this->record->store->currency ?? 'try'))
                                    ->icon('heroicon-m-calculator')
                                    ->iconPosition(IconPosition::Before),
                                TextEntry::make('total_returns')
                                    ->label('İade Sayısı')
                                    ->numeric()
                                    ->icon('heroicon-m-arrow-uturn-left')
                                    ->iconPosition(IconPosition::Before)
                                    ->color(fn () => $this->record->total_returns > 0 ? 'warning' : 'gray'),
                                TextEntry::make('return_rate')
                                    ->label('İade Oranı')
                                    ->getStateUsing(fn () => $this->record->getReturnRate() . '%')
                                    ->badge()
                                    ->color(fn (): string => match(true) {
                                        $this->record->getReturnRate() > 30 => 'danger',
                                        $this->record->getReturnRate() > 15 => 'warning',
                                        default => 'success',
                                    }),
                                TextEntry::make('days_since_last_order')
                                    ->label('Son Siparişten Beri')
                                    ->getStateUsing(fn () => $this->record->getDaysSinceLastOrder() . ' gün')
                                    ->badge()
                                    ->color(fn (): string => match(true) {
                                        $this->record->getDaysSinceLastOrder() > 90 => 'danger',
                                        $this->record->getDaysSinceLastOrder() > 30 => 'warning',
                                        default => 'success',
                                    }),
                            ]),
                    ]),
                
                Section::make('RFM Analizi')
                    ->description('Recency (Yenilik), Frequency (Sıklık), Monetary (Parasal) değer analizi')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                ViewEntry::make('rfm_recency')
                                    ->label('Yenilik Skoru')
                                    ->view('filament.infolists.rfm-score', [
                                        'score' => $this->record->getRFMRecencyScore(),
                                        'label' => 'Son alışveriş zamanı',
                                        'color' => match($this->record->getRFMRecencyScore()) {
                                            5, 4 => 'success',
                                            3 => 'warning',
                                            default => 'danger',
                                        },
                                    ]),
                                ViewEntry::make('rfm_frequency')
                                    ->label('Sıklık Skoru')
                                    ->view('filament.infolists.rfm-score', [
                                        'score' => $this->record->getRFMFrequencyScore(),
                                        'label' => 'Alışveriş sıklığı',
                                        'color' => match($this->record->getRFMFrequencyScore()) {
                                            5, 4 => 'success',
                                            3 => 'warning',
                                            default => 'danger',
                                        },
                                    ]),
                                ViewEntry::make('rfm_monetary')
                                    ->label('Parasal Skor')
                                    ->view('filament.infolists.rfm-score', [
                                        'score' => $this->record->getRFMMonetaryScore(),
                                        'label' => 'Harcama miktarı',
                                        'color' => match($this->record->getRFMMonetaryScore()) {
                                            5, 4 => 'success',
                                            3 => 'warning',
                                            default => 'danger',
                                        },
                                    ]),
                            ]),
                    ])
                    ->collapsible(),
                
                Section::make('Etiketler ve Notlar')
                    ->schema([
                        TextEntry::make('tags')
                            ->label('Etiketler')
                            ->badge()
                            ->separator(',')
                            ->default('—'),
                        TextEntry::make('notes')
                            ->label('Notlar')
                            ->markdown()
                            ->columnSpanFull()
                            ->default('—'),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Section::make('Müşteri Zaman Çizelgesi')
                    ->description('Müşteri ile ilgili tüm aktiviteler')
                    ->schema([
                        ViewEntry::make('timeline')
                            ->label('')
                            ->view('filament.infolists.customer-timeline', [
                                'events' => $this->record->timelineEvents()
                                    ->orderBy('created_at', 'desc')
                                    ->limit(50)
                                    ->get(),
                            ]),
                    ])
                    ->collapsible(false),
            ]);
    }
}