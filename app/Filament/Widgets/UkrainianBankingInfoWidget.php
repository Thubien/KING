<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class UkrainianBankingInfoWidget extends Widget
{
    protected static string $view = 'filament.widgets.ukrainian-banking-info';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        // Only show for Ukrainian banks or when creating Ukrainian bank accounts
        $record = request()->route('record');
        if ($record && $record->country_code === 'UA') {
            return true;
        }
        
        // Show on create page if user is adding Ukrainian bank
        return request()->routeIs('filament.admin.resources.bank-accounts.create');
    }

    public function getViewData(): array
    {
        return [
            'popular_banks' => [
                'PrivatBank' => [
                    'name' => 'PrivatBank',
                    'mfo' => '305299',
                    'status' => 'active',
                    'note' => 'Largest private bank in Ukraine'
                ],
                'Oschadbank' => [
                    'name' => 'State Savings Bank of Ukraine',
                    'mfo' => '300012',
                    'status' => 'active',
                    'note' => 'State-owned bank'
                ],
                'Raiffeisen Bank Aval' => [
                    'name' => 'Raiffeisen Bank Aval',
                    'mfo' => '380805',
                    'status' => 'active',
                    'note' => 'Austrian-owned, strong international presence'
                ],
                'PUMB' => [
                    'name' => 'First Ukrainian International Bank',
                    'mfo' => '254751',
                    'status' => 'active',
                    'note' => 'First private bank in Ukraine'
                ],
                'UkrSibbank' => [
                    'name' => 'UkrSibbank',
                    'mfo' => '351005',
                    'status' => 'active',
                    'note' => 'BNP Paribas subsidiary'
                ]
            ],
            'important_notes' => [
                '🇺🇦 Ukrainian banks use MFO codes instead of SWIFT for domestic transfers',
                '💱 UAH (Ukrainian Hryvnia) is the official currency',
                '🏦 IBAN format: UA + 2 check digits + 6-digit MFO + 19-digit account number',
                '⚠️ Due to ongoing conflict, some banking services may be limited',
                '🌐 International transfers may require additional verification',
                '📱 Most Ukrainian banks offer strong mobile banking solutions'
            ]
        ];
    }
}