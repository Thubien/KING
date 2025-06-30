<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCredit extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',
        'store_id',
        'return_request_id',
        'code',
        'amount',
        'remaining_amount',
        'currency',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'issued_at',
        'expires_at',
        'used_at',
        'last_used_at',
        'usage_history',
        'notes',
        'issued_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'usage_history' => 'array',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'active',
        'usage_history' => '[]',
    ];

    // İlişkiler
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
                    ->where('status', '!=', 'fully_used');
    }

    // Methods
    public function isValid(): bool
    {
        return $this->status === 'active' && 
               $this->remaining_amount > 0 && 
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canBeUsed(float $amount = null): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($amount && $amount > $this->remaining_amount) {
            return false;
        }

        return true;
    }

    /**
     * Store credit kullan
     */
    public function use(float $amount, int $transactionId = null): bool
    {
        if (!$this->canBeUsed($amount)) {
            return false;
        }

        // Kalan tutarı güncelle
        $this->remaining_amount -= $amount;

        // Kullanım geçmişine ekle
        $history = $this->usage_history ?? [];
        $history[] = [
            'date' => now()->toDateTimeString(),
            'amount' => $amount,
            'transaction_id' => $transactionId,
            'remaining' => $this->remaining_amount,
            'user' => auth()->user()->name ?? 'System',
        ];
        $this->usage_history = $history;

        // Durum güncelle
        if ($this->remaining_amount <= 0) {
            $this->status = 'fully_used';
            $this->used_at = now();
        } else {
            $this->status = 'partially_used';
        }

        $this->last_used_at = now();
        
        return $this->save();
    }

    /**
     * Store credit iptal et
     */
    public function cancel(string $reason = null): bool
    {
        if ($this->status === 'fully_used') {
            return false;
        }

        $this->status = 'cancelled';
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . 
                          "İptal nedeni: " . $reason . " (" . now()->format('d.m.Y H:i') . ")";
        }

        return $this->save();
    }

    /**
     * Otomatik expire kontrolü
     */
    public function checkAndExpire(): bool
    {
        if ($this->isExpired() && !in_array($this->status, ['fully_used', 'expired', 'cancelled'])) {
            $this->status = 'expired';
            return $this->save();
        }

        return false;
    }

    /**
     * Formatlanmış kod
     */
    public function getFormattedCodeAttribute(): string
    {
        return $this->code;
    }

    /**
     * Formatlanmış tutar
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Formatlanmış kalan tutar
     */
    public function getFormattedRemainingAttribute(): string
    {
        return number_format($this->remaining_amount, 2) . ' ' . $this->currency;
    }

    /**
     * Kullanım yüzdesi
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->amount == 0) {
            return 0;
        }

        return round((($this->amount - $this->remaining_amount) / $this->amount) * 100, 2);
    }

    /**
     * Durum rengi
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'green',
            'partially_used' => 'blue',
            'fully_used' => 'gray',
            'expired' => 'red',
            'cancelled' => 'yellow',
            default => 'gray'
        };
    }

    /**
     * Durum etiketi
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Aktif',
            'partially_used' => 'Kısmen Kullanıldı',
            'fully_used' => 'Tamamen Kullanıldı',
            'expired' => 'Süresi Doldu',
            'cancelled' => 'İptal Edildi',
            default => $this->status
        };
    }
}