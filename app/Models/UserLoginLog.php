<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jenssegers\Agent\Agent;

class UserLoginLog extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'location',
        'is_successful',
        'failure_reason',
        'logged_in_at',
        'logged_out_at',
    ];

    protected $casts = [
        'is_successful' => 'boolean',
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User agent'tan cihaz bilgilerini parse et
     */
    public static function parseUserAgent(string $userAgent): array
    {
        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        return [
            'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
            'browser' => $agent->browser() ?: 'Unknown',
            'platform' => $agent->platform() ?: 'Unknown',
        ];
    }

    /**
     * Yeni login logu oluştur
     */
    public static function createLog(int $userId, string $ipAddress, string $userAgent, bool $isSuccessful = true, ?string $failureReason = null): self
    {
        $agentInfo = self::parseUserAgent($userAgent);

        return self::create([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => $agentInfo['device_type'],
            'browser' => $agentInfo['browser'],
            'platform' => $agentInfo['platform'],
            'is_successful' => $isSuccessful,
            'failure_reason' => $failureReason,
            'logged_in_at' => now(),
        ]);
    }

    /**
     * Logout zamanını güncelle
     */
    public function markAsLoggedOut(): void
    {
        $this->update(['logged_out_at' => now()]);
    }

    /**
     * Oturum süresi (dakika)
     */
    public function getSessionDurationAttribute(): ?int
    {
        if (!$this->logged_out_at) {
            return null;
        }

        return $this->logged_in_at->diffInMinutes($this->logged_out_at);
    }

    /**
     * Formatlanmış oturum süresi
     */
    public function getFormattedDurationAttribute(): ?string
    {
        $duration = $this->session_duration;
        
        if (!$duration) {
            return 'Aktif';
        }

        if ($duration < 60) {
            return $duration . ' dakika';
        }

        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        return $hours . ' saat ' . ($minutes > 0 ? $minutes . ' dakika' : '');
    }

    /**
     * Cihaz ikonu
     */
    public function getDeviceIconAttribute(): string
    {
        return match($this->device_type) {
            'mobile' => 'heroicon-o-device-phone-mobile',
            'tablet' => 'heroicon-o-device-tablet',
            default => 'heroicon-o-computer-desktop',
        };
    }
}