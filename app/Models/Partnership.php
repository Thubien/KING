<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use App\Mail\PartnerInvitationMail;
use Illuminate\Support\Facades\Mail;

class Partnership extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id', 
        'partner_email',
        'invitation_token',
        'invited_at',
        'activated_at',
        'ownership_percentage',
        'debt_balance',
        'debt_last_updated_at',
        'role',
        'role_description',
        'partnership_start_date',
        'partnership_end_date',
        'status',
        'permissions',
        'notes',
    ];

    protected $casts = [
        'permissions' => 'array',
        'partnership_start_date' => 'date',
        'partnership_end_date' => 'date',
        'ownership_percentage' => 'decimal:2',
        'debt_balance' => 'decimal:2',
        'debt_last_updated_at' => 'datetime',
        'invited_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function settlements()
    {
        return $this->hasMany(Settlement::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopePendingInvitation($query)
    {
        return $query->where('status', 'PENDING_INVITATION');
    }

    public function scopeNotExpired($query)
    {
        return $query->where('invited_at', '>', now()->subDays(7));
    }

    // Validation Method
    public function validateOwnershipPercentage(): void
    {
        if ($this->ownership_percentage < 0.01 || $this->ownership_percentage > 100.00) {
            throw ValidationException::withMessages([
                'ownership_percentage' => 'Ownership percentage must be between 0.01% and 100.00%.'
            ]);
        }

        $currentTotal = $this->store->partnerships()
            ->where('status', 'ACTIVE')
            ->where('id', '!=', $this->id ?? 0)
            ->sum('ownership_percentage');

        if (($currentTotal + $this->ownership_percentage) > 100.01) {
            throw ValidationException::withMessages([
                'ownership_percentage' => 'Total ownership cannot exceed 100%.'
            ]);
        }
    }

    // Business Logic
    public function calculateProfitShare(float $totalProfit): float
    {
        return $totalProfit * ($this->ownership_percentage / 100);
    }
    
    // Debt Management Methods
    public function addDebt(float $amount, string $description = null): void
    {
        $this->debt_balance += $amount;
        $this->debt_last_updated_at = now();
        $this->save();
        
        // Log the debt transaction
        \Log::info('Partner debt increased', [
            'partnership_id' => $this->id,
            'partner_id' => $this->user_id,
            'store_id' => $this->store_id,
            'amount' => $amount,
            'new_balance' => $this->debt_balance,
            'description' => $description
        ]);
    }
    
    public function reduceDebt(float $amount, string $description = null): void
    {
        $this->debt_balance -= $amount;
        $this->debt_last_updated_at = now();
        $this->save();
        
        // Log the debt transaction
        \Log::info('Partner debt reduced', [
            'partnership_id' => $this->id,
            'partner_id' => $this->user_id,
            'store_id' => $this->store_id,
            'amount' => $amount,
            'new_balance' => $this->debt_balance,
            'description' => $description
        ]);
    }
    
    public function hasDebt(): bool
    {
        return $this->debt_balance > 0;
    }
    
    public function hasCredit(): bool
    {
        return $this->debt_balance < 0;
    }
    
    public function getDebtStatus(): string
    {
        if ($this->debt_balance > 0) {
            return 'owes_money';
        } elseif ($this->debt_balance < 0) {
            return 'has_credit';
        }
        return 'no_debt';
    }
    
    public function getFormattedDebtBalance(): string
    {
        $amount = abs($this->debt_balance);
        $currency = $this->store->currency ?? 'USD';
        
        if ($this->debt_balance > 0) {
            return "-{$currency} " . number_format($amount, 2); // Owes money
        } elseif ($this->debt_balance < 0) {
            return "+{$currency} " . number_format($amount, 2); // Has credit
        }
        return "{$currency} 0.00";
    }

    // Invitation Methods
    public function generateInvitationToken(): string
    {
        $this->invitation_token = bin2hex(random_bytes(32));
        $this->invited_at = now();
        $this->status = 'PENDING_INVITATION';
        $this->save();

        return $this->invitation_token;
    }

    public function sendInvitationEmail(): void
    {
        if (!$this->partner_email) {
            throw new \Exception('Partner email is required to send invitation.');
        }

        if (!$this->invitation_token) {
            $this->generateInvitationToken();
        }

        \Mail::to($this->partner_email)->send(new \App\Mail\PartnerInvitationMail($this));
    }

    public function isInvitationValid(): bool
    {
        return $this->status === 'PENDING_INVITATION' 
            && $this->invitation_token 
            && $this->invited_at 
            && $this->invited_at->greaterThan(now()->subDays(7));
    }

    public function isInvitationExpired(): bool
    {
        return $this->invited_at && $this->invited_at->lessThanOrEqualTo(now()->subDays(7));
    }

    public function activatePartnership(User $user): void
    {
        $this->update([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'activated_at' => now(),
            'invitation_token' => null,
        ]);
    }

    // Static Helper Methods
    public static function getAvailableOwnershipForStore(int $storeId): float
    {
        $currentTotal = static::where('store_id', $storeId)
            ->where('status', 'ACTIVE')
            ->sum('ownership_percentage');

        return 100.00 - $currentTotal;
    }

    public static function getTotalOwnershipForStore(int $storeId): float
    {
        return static::where('store_id', $storeId)
            ->where('status', 'ACTIVE')
            ->sum('ownership_percentage');
    }

    /**
     * Boot method to handle cache invalidation
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($partnership) {
            $partnership->clearRelatedCache();
        });

        static::deleted(function ($partnership) {
            $partnership->clearRelatedCache();
        });
    }

    /**
     * Clear cache when partnership changes
     */
    protected function clearRelatedCache(): void
    {
        if ($this->user_id) {
            Cache::forget("user:{$this->user_id}:accessible_stores");
            Cache::forget("user:{$this->user_id}:active_partnerships");
            Cache::forget("user:{$this->user_id}:total_ownership");
        }
        
        Cache::forget("store:{$this->store_id}:partnerships");
        Cache::forget("store:{$this->store_id}:total_ownership");
    }
}
