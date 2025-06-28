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
        // Premium Feature Fields
        'api_integrations_enabled',
        'webhooks_enabled',
        'real_time_sync_enabled',
        'api_calls_this_month',
        'max_api_calls_per_month',
        'stripe_customer_id',
        'stripe_subscription_id',
        'last_payment_at',
        'next_billing_date',
    ];

    protected $casts = [
        'settings' => 'array',
        'plan_expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'is_trial' => 'boolean',
        'api_integrations_enabled' => 'boolean',
        'webhooks_enabled' => 'boolean',
        'real_time_sync_enabled' => 'boolean',
        'last_payment_at' => 'datetime',
        'next_billing_date' => 'datetime',
    ];

    protected $dates = [
        'plan_expires_at',
        'trial_ends_at',
        'last_payment_at',
        'next_billing_date',
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

    //  Premium Plan Management Methods
    public function getSubscriptionPlan(): string
    {
        // Map existing plan enum to subscription plan naming
        return match($this->plan) {
            'starter' => 'free',
            'professional' => 'premium',
            'enterprise' => 'enterprise',
            default => 'free',
        };
    }

    public function canUseApiIntegrations(): bool
    {
        return $this->api_integrations_enabled || 
               $this->getSubscriptionPlan() !== 'free' || 
               $this->isOnTrial();
    }

    public function canUseWebhooks(): bool
    {
        return $this->webhooks_enabled || 
               in_array($this->getSubscriptionPlan(), ['premium', 'enterprise']) || 
               $this->isOnTrial();
    }

    public function canUseRealTimeSync(): bool
    {
        return $this->real_time_sync_enabled || 
               $this->getSubscriptionPlan() === 'enterprise' || 
               $this->isOnTrial();
    }

    public function getMaxApiCallsPerMonth(): int
    {
        if ($this->max_api_calls_per_month > 0) {
            return $this->max_api_calls_per_month;
        }

        return match($this->getSubscriptionPlan()) {
            'free' => 0,
            'premium' => 10000,
            'enterprise' => 100000,
            default => 0,
        };
    }

    public function getRemainingApiCalls(): int
    {
        return max(0, $this->getMaxApiCallsPerMonth() - $this->api_calls_this_month);
    }

    public function incrementApiUsage(int $calls = 1): void
    {
        $this->increment('api_calls_this_month', $calls);
    }

    public function resetMonthlyApiUsage(): void
    {
        $this->update(['api_calls_this_month' => 0]);
    }

    public function isApiLimitExceeded(): bool
    {
        return $this->api_calls_this_month >= $this->getMaxApiCallsPerMonth();
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['is_trial_expired'] = $this->isTrialExpired();
        $array['trial_days_remaining'] = $this->getTrialDaysRemaining();
        $array['max_stores'] = $this->getMaxStores();
        $array['can_add_store'] = $this->canAddStore();
        $array['subscription_plan'] = $this->getSubscriptionPlan();
        $array['can_use_api_integrations'] = $this->canUseApiIntegrations();
        $array['remaining_api_calls'] = $this->getRemainingApiCalls();
        
        return $array;
    }
}
