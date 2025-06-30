<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'title',
        'type',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'type' => 'both',
        'is_default' => false,
        'sort_order' => 0,
    ];

    // Constants
    const TYPE_BILLING = 'billing';
    const TYPE_SHIPPING = 'shipping';
    const TYPE_BOTH = 'both';

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeBilling($query)
    {
        return $query->whereIn('type', [self::TYPE_BILLING, self::TYPE_BOTH]);
    }

    public function scopeShipping($query)
    {
        return $query->whereIn('type', [self::TYPE_SHIPPING, self::TYPE_BOTH]);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Helper Methods
    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address_line_1,
            $this->address_line_2,
            $this->city . ($this->state ? ', ' . $this->state : ''),
            $this->postal_code,
            $this->getCountryName(),
        ];

        return implode("\n", array_filter($parts));
    }

    public function getCountryName(): string
    {
        $countries = [
            'TR' => 'Türkiye',
            'US' => 'Amerika Birleşik Devletleri',
            'GB' => 'İngiltere',
            'DE' => 'Almanya',
            'FR' => 'Fransa',
        ];

        return $countries[$this->country] ?? $this->country;
    }

    public function canBeBilling(): bool
    {
        return in_array($this->type, [self::TYPE_BILLING, self::TYPE_BOTH]);
    }

    public function canBeShipping(): bool
    {
        return in_array($this->type, [self::TYPE_SHIPPING, self::TYPE_BOTH]);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($address) {
            // If this is the first address, make it default
            if (!$address->customer->addresses()->exists()) {
                $address->is_default = true;
            }
            
            // If this is set as default, remove default from others
            if ($address->is_default) {
                $address->customer->addresses()->update(['is_default' => false]);
            }
        });

        static::updating(function ($address) {
            // If this is set as default, remove default from others
            if ($address->is_default && $address->wasChanged('is_default')) {
                $address->customer->addresses()
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}