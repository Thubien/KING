<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'store_id',
        'name',
        'email',
        'phone',
        'gender',
        'birth_date',
        'tags',
        'notes',
        'source',
        'status',
        'first_order_date',
        'last_order_date',
        'total_orders',
        'total_spent',
        'total_spent_usd',
        'total_returns',
        'total_return_amount',
        'avg_order_value',
        'lifetime_value',
        'accepts_marketing',
        'preferred_contact_method',
        'whatsapp_number',
        'tax_number',
        'company_name',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'birth_date' => 'date',
        'first_order_date' => 'date',
        'last_order_date' => 'date',
        'total_spent' => 'decimal:2',
        'total_spent_usd' => 'decimal:2',
        'total_return_amount' => 'decimal:2',
        'avg_order_value' => 'decimal:2',
        'lifetime_value' => 'decimal:2',
        'accepts_marketing' => 'boolean',
    ];

    protected $attributes = [
        'tags' => '[]',
        'status' => 'active',
        'source' => 'manual',
        'accepts_marketing' => true,
        'total_orders' => 0,
        'total_spent' => 0,
        'total_spent_usd' => 0,
        'total_returns' => 0,
        'total_return_amount' => 0,
        'avg_order_value' => 0,
        'lifetime_value' => 0,
    ];

    // Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BLACKLIST = 'blacklist';

    const SOURCE_MANUAL = 'manual';
    const SOURCE_SHOPIFY = 'shopify';
    const SOURCE_RETURN = 'return';
    const SOURCE_IMPORT = 'import';

    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';
    const GENDER_OTHER = 'other';

    const CONTACT_PHONE = 'phone';
    const CONTACT_WHATSAPP = 'whatsapp';
    const CONTACT_EMAIL = 'email';
    const CONTACT_SMS = 'sms';

    // Common tags
    const TAG_VIP = 'vip';
    const TAG_WHOLESALE = 'wholesale';
    const TAG_PROBLEMATIC = 'problematic';
    const TAG_RETURNING = 'returning';
    const TAG_NEW = 'new';
    const TAG_LOYAL = 'loyal';

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function storeCredits(): HasMany
    {
        return $this->hasMany(StoreCredit::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(CustomerTimelineEvent::class)->orderBy('created_at', 'desc');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeVip(Builder $query): Builder
    {
        return $query->whereJsonContains('tags', self::TAG_VIP);
    }

    public function scopeWithTag(Builder $query, string $tag): Builder
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeAcceptsMarketing(Builder $query): Builder
    {
        return $query->where('accepts_marketing', true);
    }

    public function scopeInactive(Builder $query, int $days = 90): Builder
    {
        return $query->where('last_order_date', '<', now()->subDays($days));
    }

    public function scopeHighValue(Builder $query, float $minValue = 10000): Builder
    {
        return $query->where('total_spent', '>=', $minValue);
    }

    public function scopeHighReturnRate(Builder $query, float $rate = 0.3): Builder
    {
        return $query->whereRaw('(total_returns::float / NULLIF(total_orders, 0)) > ?', [$rate]);
    }

    // Helper Methods
    public function getDefaultAddress(): ?CustomerAddress
    {
        return $this->addresses()->where('is_default', true)->first()
            ?? $this->addresses()->first();
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
            
            // Log timeline event
            $this->logTimelineEvent('tag_added', "Etiket eklendi: {$tag}");
        }
    }

    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $tags = array_values(array_diff($tags, [$tag]));
        $this->update(['tags' => $tags]);
        
        // Log timeline event
        $this->logTimelineEvent('tag_removed', "Etiket kaldırıldı: {$tag}");
    }

    public function getReturnRate(): float
    {
        if ($this->total_orders == 0) {
            return 0;
        }
        return round(($this->total_returns / $this->total_orders) * 100, 2);
    }

    public function getDaysSinceLastOrder(): ?int
    {
        if (!$this->last_order_date) {
            return null;
        }
        return $this->last_order_date->diffInDays(now());
    }

    public function isVip(): bool
    {
        return $this->hasTag(self::TAG_VIP);
    }

    public function isAtRisk(): bool
    {
        // High return rate or long time since last order
        return $this->getReturnRate() > 30 || $this->getDaysSinceLastOrder() > 90;
    }

    public function getSegment(): string
    {
        if ($this->isVip()) {
            return 'VIP';
        }
        
        if ($this->total_orders === 0) {
            return 'Potansiyel';
        }
        
        if ($this->total_orders === 1) {
            return 'Yeni Müşteri';
        }
        
        if ($this->getDaysSinceLastOrder() > 180) {
            return 'Kayıp Müşteri';
        }
        
        if ($this->getDaysSinceLastOrder() > 90) {
            return 'Risk Altında';
        }
        
        if ($this->total_orders > 5) {
            return 'Sadık Müşteri';
        }
        
        return 'Normal Müşteri';
    }
    
    public function isLost(): bool
    {
        return $this->getDaysSinceLastOrder() > 180;
    }
    
    /**
     * RFM Analysis Methods
     */
    public function getRFMRecencyScore(): int
    {
        if (!$this->last_order_date) {
            return 1;
        }
        
        $daysSinceLastOrder = $this->getDaysSinceLastOrder();
        
        return match(true) {
            $daysSinceLastOrder <= 30 => 5,
            $daysSinceLastOrder <= 60 => 4,
            $daysSinceLastOrder <= 90 => 3,
            $daysSinceLastOrder <= 180 => 2,
            default => 1,
        };
    }
    
    public function getRFMFrequencyScore(): int
    {
        return match(true) {
            $this->total_orders >= 10 => 5,
            $this->total_orders >= 6 => 4,
            $this->total_orders >= 3 => 3,
            $this->total_orders >= 1 => 2,
            default => 1,
        };
    }
    
    public function getRFMMonetaryScore(): int
    {
        // Get average order value for the store to compare
        $storeAvgOrderValue = $this->store->transactions()
            ->where('category', 'SALES')
            ->where('type', 'income')
            ->avg('amount') ?? 100;
        
        $customerAvgOrderValue = $this->avg_order_value;
        $ratio = $customerAvgOrderValue / $storeAvgOrderValue;
        
        return match(true) {
            $ratio >= 2.0 => 5,
            $ratio >= 1.5 => 4,
            $ratio >= 1.0 => 3,
            $ratio >= 0.5 => 2,
            default => 1,
        };
    }
    
    public function getRFMScore(): string
    {
        $r = $this->getRFMRecencyScore();
        $f = $this->getRFMFrequencyScore();
        $m = $this->getRFMMonetaryScore();
        
        return "{$r}{$f}{$m}";
    }

    // Statistics Update Methods
    public function updateStatistics(): void
    {
        $transactions = $this->transactions()
            ->where('status', Transaction::STATUS_APPROVED)
            ->where('type', 'income')
            ->where('category', 'SALES');

        $this->total_orders = $transactions->count();
        $this->total_spent = $transactions->sum('amount');
        $this->total_spent_usd = $transactions->sum('amount_usd');
        
        if ($this->total_orders > 0) {
            $this->avg_order_value = $this->total_spent / $this->total_orders;
            $this->first_order_date = $transactions->min('transaction_date');
            $this->last_order_date = $transactions->max('transaction_date');
        }

        $this->total_returns = $this->returnRequests()
            ->where('status', 'completed')
            ->count();
            
        $this->total_return_amount = $this->returnRequests()
            ->where('status', 'completed')
            ->sum('refund_amount');

        // Calculate LTV (simplified: total spent + potential future value)
        $this->lifetime_value = $this->total_spent;

        $this->save();
    }

    // Timeline Event Logging
    public function logTimelineEvent(
        string $eventType, 
        string $title, 
        ?string $description = null,
        ?array $metadata = null,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): CustomerTimelineEvent {
        return $this->timelineEvents()->create([
            'event_type' => $eventType,
            'event_title' => $title,
            'event_description' => $description,
            'event_data' => $metadata,
            'related_model' => $relatedType,
            'related_id' => $relatedId,
            'created_by' => auth()->id(),
        ]);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::created(function ($customer) {
            // Log customer creation
            $customer->logTimelineEvent(
                'customer_created',
                'Müşteri oluşturuldu',
                "Kaynak: {$customer->source}"
            );

            // Add new customer tag
            if ($customer->total_orders === 0) {
                $customer->addTag(self::TAG_NEW);
            }
        });

        static::updated(function ($customer) {
            // Auto-tagging based on conditions
            if ($customer->wasChanged('total_spent')) {
                if ($customer->total_spent >= 10000 && !$customer->hasTag(self::TAG_VIP)) {
                    $customer->addTag(self::TAG_VIP);
                }
            }

            if ($customer->wasChanged('status')) {
                $customer->logTimelineEvent(
                    'status_changed',
                    'Durum değiştirildi',
                    "Yeni durum: {$customer->status}"
                );
            }
        });
    }

    // Static helper to find or create customer
    public static function findOrCreateFromData(array $data, Store $store): ?self
    {
        if (empty($data['phone']) && empty($data['email'])) {
            return null;
        }

        $query = self::where('store_id', $store->id);

        if (!empty($data['phone'])) {
            $query->where('phone', $data['phone']);
        } elseif (!empty($data['email'])) {
            $query->where('email', $data['email']);
        }

        $customer = $query->first();

        if (!$customer) {
            $customer = self::create([
                'company_id' => $store->company_id,
                'store_id' => $store->id,
                'name' => $data['name'] ?? 'Bilinmeyen Müşteri',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'source' => $data['source'] ?? 'manual',
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return $customer;
    }
}