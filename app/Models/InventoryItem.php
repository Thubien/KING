<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'sku',
        'name',
        'description',
        'quantity',
        'unit_cost',
        'currency',
        'total_value',
        'supplier',
        'category',
        'location',
        'reorder_point',
        'reorder_quantity',
        'last_restocked_at',
        'last_counted_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'last_restocked_at' => 'datetime',
        'last_counted_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // Calculated attributes
    public function getFormattedValueAttribute(): string
    {
        return $this->currency.' '.number_format($this->total_value, 2);
    }

    public function getFormattedUnitCostAttribute(): string
    {
        return $this->currency.' '.number_format($this->unit_cost, 2);
    }

    // Business logic
    public function calculateTotalValue(): float
    {
        $this->total_value = $this->quantity * $this->unit_cost;

        return $this->total_value;
    }

    public function needsReorder(): bool
    {
        return $this->quantity <= $this->reorder_point;
    }

    public function adjustQuantity(int $adjustment, string $reason, ?int $userId = null): void
    {
        $oldQuantity = $this->quantity;
        $this->quantity += $adjustment;
        $this->calculateTotalValue();
        $this->save();

        // Record movement
        $this->movements()->create([
            'movement_type' => $adjustment > 0 ? 'IN' : 'OUT',
            'quantity' => abs($adjustment),
            'unit_cost' => $this->unit_cost,
            'total_cost' => abs($adjustment) * $this->unit_cost,
            'reason' => $reason,
            'user_id' => $userId ?? auth()->id(),
            'old_quantity' => $oldQuantity,
            'new_quantity' => $this->quantity,
        ]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity <= reorder_point');
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }
}
