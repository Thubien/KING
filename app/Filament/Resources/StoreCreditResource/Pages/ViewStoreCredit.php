<?php

namespace App\Filament\Resources\StoreCreditResource\Pages;

use App\Filament\Resources\StoreCreditResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewStoreCredit extends ViewRecord
{
    protected static string $resource = StoreCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => !in_array($this->record->status, ['fully_used', 'cancelled'])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Store Credit Detayları')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('code')
                                    ->label('Kod')
                                    ->copyable()
                                    ->weight('bold')
                                    ->size('lg'),
                                
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Durum')
                                    ->badge()
                                    ->color(fn (string $state): string => match($state) {
                                        'active' => 'success',
                                        'partially_used' => 'warning',
                                        'fully_used' => 'danger',
                                        'expired' => 'danger',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'active' => 'Aktif',
                                        'partially_used' => 'Kısmen Kullanılmış',
                                        'fully_used' => 'Tamamen Kullanılmış',
                                        'expired' => 'Süresi Dolmuş',
                                        'cancelled' => 'İptal Edilmiş',
                                        default => $state,
                                    }),
                                
                                Infolists\Components\TextEntry::make('store.name')
                                    ->label('Mağaza'),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Tutar Bilgileri')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Toplam Tutar')
                                    ->money(fn () => strtolower($this->record->currency))
                                    ->size('lg')
                                    ->weight('bold'),
                                
                                Infolists\Components\TextEntry::make('remaining_amount')
                                    ->label('Kalan Tutar')
                                    ->money(fn () => strtolower($this->record->currency))
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color(fn () => 
                                        $this->record->remaining_amount == 0 ? 'danger' : 
                                        ($this->record->remaining_amount < $this->record->amount ? 'warning' : 'success')
                                    ),
                                
                                Infolists\Components\TextEntry::make('used_amount')
                                    ->label('Kullanılan Tutar')
                                    ->getStateUsing(fn () => $this->record->amount - $this->record->remaining_amount)
                                    ->money(fn () => strtolower($this->record->currency))
                                    ->color('gray'),
                                
                                Infolists\Components\TextEntry::make('usage_percentage')
                                    ->label('Kullanım Oranı')
                                    ->getStateUsing(fn () => 
                                        $this->record->amount > 0 
                                            ? round((($this->record->amount - $this->record->remaining_amount) / $this->record->amount) * 100) . '%'
                                            : '0%'
                                    )
                                    ->badge()
                                    ->color(fn (string $state): string => match(true) {
                                        $state === '0%' => 'success',
                                        $state === '100%' => 'danger',
                                        default => 'warning',
                                    }),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Müşteri Bilgileri')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer_name')
                                    ->label('Müşteri Adı')
                                    ->icon('heroicon-o-user'),
                                
                                Infolists\Components\TextEntry::make('customer_email')
                                    ->label('E-posta')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                
                                Infolists\Components\TextEntry::make('customer_phone')
                                    ->label('Telefon')
                                    ->icon('heroicon-o-phone')
                                    ->placeholder('-'),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Tarih Bilgileri')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('issued_at')
                                    ->label('Düzenlenme Tarihi')
                                    ->dateTime('d/m/Y H:i'),
                                
                                Infolists\Components\TextEntry::make('expires_at')
                                    ->label('Son Geçerlilik Tarihi')
                                    ->dateTime('d/m/Y H:i')
                                    ->color(fn () => 
                                        $this->record->expires_at && now()->gt($this->record->expires_at) ? 'danger' : 'gray'
                                    ),
                                
                                Infolists\Components\TextEntry::make('used_at')
                                    ->label('İlk Kullanım')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-'),
                                
                                Infolists\Components\TextEntry::make('last_used_at')
                                    ->label('Son Kullanım')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-'),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('İlişkili Kayıtlar')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('returnRequest.id')
                                    ->label('İade Talebi')
                                    ->formatStateUsing(fn ($state) => $state ? "İade Talebi #{$state}" : '-')
                                    ->url(fn () => 
                                        $this->record->return_request_id 
                                            ? route('filament.admin.resources.return-requests.view', $this->record->return_request_id)
                                            : null
                                    )
                                    ->color('primary')
                                    ->icon('heroicon-o-arrow-uturn-left'),
                                
                                Infolists\Components\TextEntry::make('issued_by')
                                    ->label('Düzenleyen')
                                    ->getStateUsing(fn () => 
                                        $this->record->issued_by 
                                            ? \App\Models\User::find($this->record->issued_by)?->name 
                                            : 'Sistem'
                                    )
                                    ->icon('heroicon-o-user-circle'),
                            ]),
                    ])
                    ->collapsed(),
                
                Infolists\Components\Section::make('Kullanım Geçmişi')
                    ->schema([
                        Infolists\Components\TextEntry::make('usage_history')
                            ->label(false)
                            ->html()
                            ->getStateUsing(function () {
                                if (!$this->record->usage_history) {
                                    return '<p class="text-gray-500">Henüz kullanılmamış</p>';
                                }
                                
                                $history = json_decode($this->record->usage_history, true);
                                if (!is_array($history) || empty($history)) {
                                    return '<p class="text-gray-500">Henüz kullanılmamış</p>';
                                }
                                
                                $html = '<div class="space-y-2">';
                                foreach ($history as $usage) {
                                    $date = \Carbon\Carbon::parse($usage['date'])->format('d/m/Y H:i');
                                    $amount = number_format($usage['amount'], 2);
                                    $reference = $usage['reference'] ?? '-';
                                    $notes = $usage['notes'] ?? '';
                                    
                                    $html .= "<div class='border-l-4 border-blue-500 pl-3 py-2'>";
                                    $html .= "<div class='font-semibold'>{$date} - {$amount} {$this->record->currency}</div>";
                                    $html .= "<div class='text-sm text-gray-600'>Ref: {$reference}</div>";
                                    if ($notes) {
                                        $html .= "<div class='text-sm text-gray-500 italic'>{$notes}</div>";
                                    }
                                    $html .= "</div>";
                                }
                                $html .= '</div>';
                                
                                return $html;
                            }),
                    ])
                    ->collapsed(fn () => !$this->record->usage_history),
                
                Infolists\Components\Section::make('Notlar')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label(false)
                            ->placeholder('Not bulunmuyor')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(fn () => !$this->record->notes),
            ]);
    }
}