<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'created_by',
        'import_batch_id',
        'transaction_id',
        'external_id',
        'reference_number',
        'amount',
        'currency',
        'exchange_rate',
        'amount_usd',
        'category',
        'subcategory',
        'type',
        'status',
        'description',
        'notes',
        'metadata',
        'transaction_date',
        'processed_at',
        'source',
        'source_details',
        'is_reconciled',
        'reconciled_at',
        'reconciled_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'transaction_date' => 'datetime',
        'processed_at' => 'datetime',
        'reconciled_at' => 'datetime',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'amount_usd' => 'decimal:2',
        'is_reconciled' => 'boolean',
    ];

    // Categories for the 11-category system
    public const CATEGORIES = [
        'revenue' => 'Revenue',
        'cost_of_goods' => 'Cost of Goods',
        'marketing' => 'Marketing',
        'shipping' => 'Shipping',
        'fees_commissions' => 'Fees & Commissions',
        'taxes' => 'Taxes',
        'refunds_returns' => 'Refunds & Returns',
        'operational' => 'Operational',
        'partnerships' => 'Partnerships',
        'investments' => 'Investments',
        'other' => 'Other',
    ];

    // Boot method
    protected static function boot()
    {
        parent::boot();
        
        // Multi-tenant scoping
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->whereHas('store', function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                });
            }
        });

        static::creating(function ($transaction) {
            if (!$transaction->transaction_id) {
                $transaction->transaction_id = 'TXN-' . strtoupper(Str::random(8));
            }
            
            if (!$transaction->created_by && auth()->check()) {
                $transaction->created_by = auth()->id();
            }

            // Convert to USD if different currency
            if ($transaction->currency !== 'USD' && !$transaction->amount_usd) {
                $transaction->amount_usd = $transaction->amount * $transaction->exchange_rate;
            } elseif ($transaction->currency === 'USD') {
                $transaction->amount_usd = $transaction->amount;
                $transaction->exchange_rate = 1.0;
            }
        });
    }

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    // Scopes
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year);
    }

    // Business Logic Methods
    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getSignedAmount(): float
    {
        return $this->isIncome() ? $this->amount_usd : -$this->amount_usd;
    }

    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Unknown';
    }

    // Static Helper Methods
    public static function getCategories(): array
    {
        return self::CATEGORIES;
    }

    public static function calculateProfit(int $storeId, string $period = 'month'): float
    {
        $query = static::where('store_id', $storeId)->where('status', 'completed');

        // Apply period filter
        switch ($period) {
            case 'day':
                $query->whereDate('transaction_date', today());
                break;
            case 'week':
                $query->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('transaction_date', now()->month);
                break;
            case 'year':
                $query->whereYear('transaction_date', now()->year);
                break;
        }

        $income = $query->clone()->where('type', 'income')->sum('amount_usd');
        $expenses = $query->clone()->where('type', 'expense')->sum('amount_usd');

        return $income - $expenses;
    }
}
