<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'description',
        'logo_url',
        'timezone',
        'currency',
        'settings',
        'status',
        'plan',
        'plan_expires_at',
        'is_trial',
        'trial_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'plan_expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'is_trial' => 'boolean',
    ];

    protected $dates = [
        'plan_expires_at',
        'trial_ends_at',
    ];

    // Boot method to auto-generate slug
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($company) {
            if (!$company->slug) {
                $company->slug = Str::slug($company->name);
                
                // Ensure unique slug
                $originalSlug = $company->slug;
                $count = 1;
                while (static::where('slug', $company->slug)->exists()) {
                    $company->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }
            
            // Set trial period for new companies
            if (!$company->trial_ends_at && $company->is_trial) {
                $company->trial_ends_at = now()->addDays(14);
            }
        });
    }

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Store::class);
    }

    public function partnerships(): HasManyThrough
    {
        return $this->hasManyThrough(Partnership::class, Store::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function paymentProcessorAccounts(): HasMany
    {
        return $this->hasMany(PaymentProcessorAccount::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOnTrial($query)
    {
        return $query->where('is_trial', true);
    }

    public function scopeSubscribed($query)
    {
        return $query->where('is_trial', false)->where('status', 'active');
    }

    // Business Logic Methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnTrial(): bool
    {
        return $this->is_trial && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isTrialExpired(): bool
    {
        return $this->is_trial && $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function hasValidSubscription(): bool
    {
        if ($this->isOnTrial()) {
            return true;
        }

        return !$this->is_trial && 
               $this->plan_expires_at && 
               $this->plan_expires_at->isFuture() && 
               $this->isActive();
    }

    public function getMaxStores(): int
    {
        return match($this->plan) {
            'starter' => 3,
            'professional' => 10,
            'enterprise' => 999,
            default => 1,
        };
    }

    public function canAddStore(): bool
    {
        return $this->stores()->count() < $this->getMaxStores() && $this->hasValidSubscription();
    }

    public function getTrialDaysRemaining(): int
    {
        if (!$this->isOnTrial()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    // Helper methods
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['is_trial_expired'] = $this->isTrialExpired();
        $array['trial_days_remaining'] = $this->getTrialDaysRemaining();
        $array['max_stores'] = $this->getMaxStores();
        $array['can_add_store'] = $this->canAddStore();
        
        return $array;
    }
}
