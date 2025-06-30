<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'transaction_id',
        'movement_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'reason',
        'reference_number',
        'user_id',
        'old_quantity',
        'new_quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'old_quantity' => 'integer',
        'new_quantity' => 'integer',
    ];

    // Movement types
    const TYPE_IN = 'IN';           // Stock addition

    const TYPE_OUT = 'OUT';         // Stock removal

    const TYPE_SALE = 'SALE';       // Sold to customer

    const TYPE_RETURN = 'RETURN';   // Customer return

    const TYPE_ADJUST = 'ADJUST';   // Manual adjustment

    const TYPE_COUNT = 'COUNT';     // Physical count adjustment

    const TYPE_DAMAGE = 'DAMAGE';   // Damaged/lost items

    const TYPE_TRANSFER = 'TRANSFER'; // Transfer between stores

    // Relationships
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function isAddition(): bool
    {
        return in_array($this->movement_type, [self::TYPE_IN, self::TYPE_RETURN]);
    }

    public function isRemoval(): bool
    {
        return in_array($this->movement_type, [self::TYPE_OUT, self::TYPE_SALE, self::TYPE_DAMAGE]);
    }

    public function getFormattedType(): string
    {
        return match ($this->movement_type) {
            self::TYPE_IN => 'Stock In',
            self::TYPE_OUT => 'Stock Out',
            self::TYPE_SALE => 'Sale',
            self::TYPE_RETURN => 'Return',
            self::TYPE_ADJUST => 'Adjustment',
            self::TYPE_COUNT => 'Count',
            self::TYPE_DAMAGE => 'Damage/Loss',
            self::TYPE_TRANSFER => 'Transfer',
            default => $this->movement_type,
        };
    }

    public function getTypeColor(): string
    {
        return match ($this->movement_type) {
            self::TYPE_IN, self::TYPE_RETURN => 'success',
            self::TYPE_OUT, self::TYPE_SALE => 'warning',
            self::TYPE_DAMAGE => 'danger',
            self::TYPE_ADJUST, self::TYPE_COUNT => 'info',
            self::TYPE_TRANSFER => 'primary',
            default => 'gray',
        };
    }
}
