<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'platform',
        'shopify_domain',
        'shopify_store_id',
        'shopify_access_token',
        'currency',
        'country_code',
        'timezone',
        'description',
        'logo_url',
        'shopify_webhook_endpoints',
        'status',
        'last_sync_at',
        'sync_errors',
        'settings',
        // Stripe API Integration (Premium Feature)
        'stripe_secret_key',
        'stripe_publishable_key',
        'stripe_sync_enabled',
        'last_stripe_sync',
    ];

    protected $casts = [
        'shopify_webhook_endpoints' => 'array',
        'sync_errors' => 'array',
        'settings' => 'array',
        'last_sync_at' => 'datetime',
        'stripe_sync_enabled' => 'boolean',
        'last_stripe_sync' => 'datetime',
    ];

    protected $hidden = [
        'shopify_access_token',
        'stripe_secret_key',
    ];

    // Boot method for global scoping
    protected static function boot()
    {
        parent::boot();

        // Global scope for multi-tenancy (only if user has company)
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('company_id', auth()->user()->company_id);
            }
        });

        static::creating(function ($store) {
            if (! $store->company_id && auth()->check()) {
                $store->company_id = auth()->user()->company_id;
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function partnerships(): HasMany
    {
        return $this->hasMany(Partnership::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function activePartnerships(): HasMany
    {
        return $this->partnerships()->where('status', 'active');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeConnected($query)
    {
        return $query->whereNotNull('shopify_access_token')->where('status', 'active');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Accessors & Mutators
    public function getShopifyAccessTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setShopifyAccessTokenAttribute($value)
    {
        $this->attributes['shopify_access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getStripeSecretKeyAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setStripeSecretKeyAttribute($value)
    {
        $this->attributes['stripe_secret_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getShopifyUrlAttribute(): string
    {
        return "https://{$this->shopify_domain}";
    }

    public function getAdminUrlAttribute(): string
    {
        return "https://{$this->shopify_domain}/admin";
    }

    // Business Logic Methods
    public function isConnected(): bool
    {
        return ! empty($this->shopify_access_token) && $this->status === 'active';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function needsSync(): bool
    {
        if (! $this->last_sync_at) {
            return true;
        }

        return $this->last_sync_at->lt(now()->subHours(1));
    }

    public function getTotalPartnerships(): int
    {
        return $this->activePartnerships()->count();
    }

    public function getTotalOwnershipPercentage(): float
    {
        return $this->activePartnerships()->sum('ownership_percentage');
    }

    public function isPartnershipComplete(): bool
    {
        return abs($this->getTotalOwnershipPercentage() - 100.0) < 0.01;
    }

    public function getPartnershipGap(): float
    {
        return round(100.0 - $this->getTotalOwnershipPercentage(), 2);
    }

    public function getRevenue(string $period = 'month'): float
    {
        $query = $this->transactions()
            ->where('type', 'income')
            ->where('category', 'revenue')
            ->where('status', 'completed');

        return match ($period) {
            'day' => $query->whereDate('transaction_date', today())->sum('amount_usd'),
            'week' => $query->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount_usd'),
            'month' => $query->whereMonth('transaction_date', now()->month)->sum('amount_usd'),
            'year' => $query->whereYear('transaction_date', now()->year)->sum('amount_usd'),
            default => $query->sum('amount_usd'),
        };
    }

    public function getProfit(string $period = 'month'): float
    {
        $query = $this->transactions()
            ->where('status', 'completed');

        $income = $query->clone()->where('type', 'income')->sum('amount_usd');
        $expenses = $query->clone()->where('type', 'expense')->sum('amount_usd');

        return $income - $expenses;
    }

    public function calculatePartnerProfits(): array
    {
        $totalProfit = $this->getProfit();
        $partnerships = $this->activePartnerships()->with('user')->get();
        $profits = [];

        foreach ($partnerships as $partnership) {
            $partnerProfit = $totalProfit * ($partnership->ownership_percentage / 100);
            $profits[] = [
                'user_id' => $partnership->user_id,
                'user_name' => $partnership->user->name,
                'ownership_percentage' => $partnership->ownership_percentage,
                'profit_amount' => round($partnerProfit, 2),
                'currency' => $this->currency,
            ];
        }

        return $profits;
    }

    public function validateDomain(): bool
    {
        return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $this->shopify_domain);
    }

    // Helper methods
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    // Inventory methods
    public function getInventoryValueAttribute(): float
    {
        return $this->inventoryItems()
            ->where('is_active', true)
            ->sum('total_value');
    }

    public function getFormattedInventoryValueAttribute(): string
    {
        return $this->currency.' '.number_format($this->inventory_value, 2);
    }

    public function getTotalInventoryItemsAttribute(): int
    {
        return $this->inventoryItems()
            ->where('is_active', true)
            ->count();
    }

    public function getLowStockItemsCountAttribute(): int
    {
        return $this->inventoryItems()
            ->where('is_active', true)
            ->whereRaw('quantity <= reorder_point')
            ->count();
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['is_connected'] = $this->isConnected();
        $array['needs_sync'] = $this->needsSync();
        $array['total_partnerships'] = $this->getTotalPartnerships();
        $array['partnership_percentage'] = $this->getTotalOwnershipPercentage();
        $array['is_partnership_complete'] = $this->isPartnershipComplete();
        $array['partnership_gap'] = $this->getPartnershipGap();
        $array['shopify_url'] = $this->shopify_url;
        $array['admin_url'] = $this->admin_url;
        $array['inventory_value'] = $this->inventory_value;
        $array['formatted_inventory_value'] = $this->formatted_inventory_value;

        return $array;
    }
}
