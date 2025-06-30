<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        // Email bildirimleri
        'email_notifications',
        'email_return_requests',
        'email_large_transactions',
        'email_transaction_threshold',
        'email_partner_activities',
        'email_weekly_report',
        'email_monthly_report',
        // App bildirimleri
        'app_notifications',
        'app_return_requests',
        'app_large_transactions',
        'app_partner_activities',
        // Çalışma tercihleri
        'default_currency',
        'date_format',
        'time_format',
        'records_per_page',
        'timezone',
        'default_store_id',
        'notification_language',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'email_return_requests' => 'boolean',
        'email_large_transactions' => 'boolean',
        'email_transaction_threshold' => 'decimal:2',
        'email_partner_activities' => 'boolean',
        'email_weekly_report' => 'boolean',
        'email_monthly_report' => 'boolean',
        'app_notifications' => 'boolean',
        'app_return_requests' => 'boolean',
        'app_large_transactions' => 'boolean',
        'app_partner_activities' => 'boolean',
        'records_per_page' => 'integer',
    ];

    protected $attributes = [
        'email_notifications' => true,
        'app_notifications' => true,
        'default_currency' => 'USD',
        'date_format' => 'd/m/Y',
        'time_format' => 'H:i',
        'records_per_page' => 25,
        'timezone' => 'Europe/Istanbul',
        'notification_language' => 'tr',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function defaultStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'default_store_id');
    }

    /**
     * Para birimi seçenekleri
     */
    public static function getCurrencyOptions(): array
    {
        return [
            'USD' => 'USD - Amerikan Doları',
            'EUR' => 'EUR - Euro',
            'TRY' => 'TRY - Türk Lirası',
        ];
    }

    /**
     * Tarih formatı seçenekleri
     */
    public static function getDateFormatOptions(): array
    {
        return [
            'd/m/Y' => 'GG/AA/YYYY (31/12/2024)',
            'm/d/Y' => 'AA/GG/YYYY (12/31/2024)',
            'Y-m-d' => 'YYYY-AA-GG (2024-12-31)',
            'd.m.Y' => 'GG.AA.YYYY (31.12.2024)',
        ];
    }

    /**
     * Saat formatı seçenekleri
     */
    public static function getTimeFormatOptions(): array
    {
        return [
            'H:i' => '24 Saat (23:59)',
            'h:i A' => '12 Saat (11:59 PM)',
        ];
    }

    /**
     * Dil seçenekleri
     */
    public static function getLanguageOptions(): array
    {
        return [
            'tr' => 'Türkçe',
            'en' => 'English',
        ];
    }

    /**
     * Sayfa başına kayıt seçenekleri
     */
    public static function getRecordsPerPageOptions(): array
    {
        return [
            10 => '10',
            25 => '25',
            50 => '50',
            100 => '100',
        ];
    }

    /**
     * Timezone seçenekleri
     */
    public static function getTimezoneOptions(): array
    {
        return [
            'Europe/Istanbul' => 'İstanbul (UTC+3)',
            'Europe/London' => 'Londra (UTC+0)',
            'Europe/Berlin' => 'Berlin (UTC+1)',
            'America/New_York' => 'New York (UTC-5)',
            'America/Los_Angeles' => 'Los Angeles (UTC-8)',
            'Asia/Dubai' => 'Dubai (UTC+4)',
        ];
    }
}