<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class Partnership extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id', 
        'ownership_percentage',
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
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
            ->where('status', 'active')
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
}
