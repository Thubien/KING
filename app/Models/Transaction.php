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
        'payment_processor_type',
        'payment_processor_id',
        'is_pending_payout',
        'payout_date',
        'is_personal_expense',
        'partner_id',
        'is_adjustment',
        'adjustment_type',
    ];

    protected $casts = [
        'metadata' => 'array',
        'transaction_date' => 'datetime',
        'processed_at' => 'datetime',
        'reconciled_at' => 'datetime',
        'payout_date' => 'datetime',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'amount_usd' => 'decimal:2',
        'is_reconciled' => 'boolean',
        'is_pending_payout' => 'boolean',
        'is_personal_expense' => 'boolean',
        'is_adjustment' => 'boolean',
    ];

    // Updated 11-category system aligned with business requirements
    public const CATEGORIES = [
        'SALES' => 'Sales Revenue', // 📈 Revenue from sales
        'RETURNS' => 'Returns & Refunds', // 🔄 Real money refunds
        'PAY-PRODUCT' => 'Product Costs', // 🟡 Product purchase costs
        'PAY-DELIVERY' => 'Delivery Costs', // 📦 Shipping costs
        'INVENTORY' => 'Inventory Value', // 📦 Current stock value
        'WITHDRAW' => 'Partner Withdrawals', // 💜 Partner withdrawals
        'END' => 'Transfer Commissions', // 📊 Personal transfer commissions
        'BANK_COM' => 'Banking Fees', // 🏦 Banking fees
        'FEE' => 'Payment Fees', // 💰 Payment processor fees
        'ADS' => 'Advertising', // 📱 Advertising spend
        'OTHER_PAY' => 'Other Expenses', // 🔧 All other expenses
    ];

    // Transaction types
    public const TYPE_INCOME = 'INCOME';
    public const TYPE_EXPENSE = 'EXPENSE';
    public const TYPE_PERSONAL = 'PERSONAL';
    public const TYPE_BUSINESS = 'BUSINESS';

    // Transaction statuses
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    // Payment processor types
    public const PROCESSOR_STRIPE = 'STRIPE';
    public const PROCESSOR_PAYPAL = 'PAYPAL';
    public const PROCESSOR_SHOPIFY = 'SHOPIFY_PAYMENTS';
    public const PROCESSOR_MANUAL = 'MANUAL';

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

        // Payment processor logic when transaction is approved
        static::updated(function ($transaction) {
            if ($transaction->wasChanged('status') && $transaction->status === 'APPROVED') {
                $transaction->handlePaymentProcessorLogic();
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

    public function paymentProcessor(): BelongsTo
    {
        return $this->belongsTo(PaymentProcessorAccount::class, 'payment_processor_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
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

    /**
     * KRITIK: Payment processor logic - Manuel CSV için
     */
    public function handlePaymentProcessorLogic(): void
    {
        // SALES kategori → Payment processor'a pending balance ekle
        if ($this->category === 'SALES' && $this->type === self::TYPE_INCOME) {
            $this->addToPendingBalance();
        }
        
        // Personal expense tracking
        if ($this->is_personal_expense && $this->partner_id) {
            $this->updatePartnerDebt();
        }
    }

    private function addToPendingBalance(): void
    {
        // Varsayılan olarak MANUAL processor kullan (CSV imports için)
        $processorType = $this->payment_processor_type ?? self::PROCESSOR_MANUAL;
        
        $processor = PaymentProcessorAccount::firstOrCreate([
            'company_id' => $this->store->company_id,
            'processor_type' => $processorType,
            'currency' => $this->currency
        ], [
            'current_balance' => 0,
            'pending_balance' => 0,
            'is_active' => true
        ]);

        $processor->addPendingBalance(
            $this->amount, 
            "Sales transaction: {$this->transaction_id}"
        );

        // Update transaction reference
        $this->update(['payment_processor_id' => $processor->id]);
    }

    private function updatePartnerDebt(): void
    {
        if (!$this->partner_id) return;

        // Partner debt tracking logic
        \Log::info('Partner debt updated', [
            'transaction_id' => $this->transaction_id,
            'partner_id' => $this->partner_id,
            'amount' => $this->amount,
            'store_id' => $this->store_id
        ]);
    }

    /**
     * Payout process - Manuel olarak çağrılacak
     */
    public function processPayoutToBank(int $bankAccountId): void
    {
        if (!$this->paymentProcessor) {
            throw new \Exception('No payment processor associated with this transaction');
        }

        $bankAccount = BankAccount::findOrFail($bankAccountId);
        $processor = $this->paymentProcessor;

        // Pending'den current'a çevir
        $processor->movePendingToCurrent(
            $this->amount,
            "Payout to bank: {$this->transaction_id}"
        );

        // Bank account'a ekle
        $bankAccount->increment('current_balance', $this->amount);

        // Transaction'ı güncelle
        $this->update([
            'is_pending_payout' => false,
            'payout_date' => now()
        ]);
    }

    // Scopes for new fields
    public function scopePendingPayout($query)
    {
        return $query->where('is_pending_payout', true);
    }

    public function scopePersonalExpenses($query)
    {
        return $query->where('is_personal_expense', true);
    }

    public function scopeByProcessor($query, string $processorType)
    {
        return $query->where('payment_processor_type', $processorType);
    }

    public function scopeByPartner($query, int $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    // Business logic helpers
    public function isPendingPayout(): bool
    {
        return $this->is_pending_payout === true;
    }

    public function isPersonalExpense(): bool
    {
        return $this->is_personal_expense === true;
    }

    public function isAdjustment(): bool
    {
        return $this->is_adjustment === true;
    }

    public function getProcessorName(): string
    {
        $names = [
            self::PROCESSOR_STRIPE => 'Stripe',
            self::PROCESSOR_PAYPAL => 'PayPal',
            self::PROCESSOR_SHOPIFY => 'Shopify Payments',
            self::PROCESSOR_MANUAL => 'Manual Entry'
        ];

        return $names[$this->payment_processor_type] ?? 'Unknown';
    }

    /**
     * Updated profit calculation with new categories
     */
    public static function calculateProfit(int $storeId, string $period = 'month'): float
    {
        $query = static::where('store_id', $storeId)->where('status', self::STATUS_APPROVED);

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

        // Revenue
        $revenue = $query->clone()->where('category', 'SALES')->sum('amount_usd');
        
        // Expenses (all non-revenue categories)
        $expenses = $query->clone()
            ->whereNotIn('category', ['SALES'])
            ->sum('amount_usd');

        return $revenue - $expenses;
    }

    /**
     * Category-based reporting
     */
    public static function getCategoryTotals(int $storeId, string $period = 'month'): array
    {
        $query = static::where('store_id', $storeId)->where('status', self::STATUS_APPROVED);

        // Apply period filter (same as above)
        switch ($period) {
            case 'month':
                $query->whereMonth('transaction_date', now()->month);
                break;
            // Add other periods as needed
        }

        $totals = [];
        foreach (self::CATEGORIES as $code => $label) {
            $totals[$code] = [
                'amount' => $query->clone()->where('category', $code)->sum('amount_usd'),
                'count' => $query->clone()->where('category', $code)->count(),
                'label' => $label
            ];
        }

        return $totals;
    }
}
