<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        // Multi-channel sales fields
        'sales_channel',
        'payment_method',
        'data_source',
        'customer_info',
        'sales_rep_id',
        'order_notes',
        'order_reference',
        // Transaction editor fields
        'assignment_status',
        'user_notes',
        'is_transfer',
        'matched_transaction_id',
        'subcategory',
        'is_split',
        'parent_transaction_id',
        'split_percentage',
        'suggestion_confidence',
        'suggested_assignment',
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
        'customer_info' => 'array',
        'is_transfer' => 'boolean',
        'is_split' => 'boolean',
        'split_percentage' => 'decimal:2',
        'suggestion_confidence' => 'integer',
        'suggested_assignment' => 'array',
    ];

    // Updated category system with expanded income categories
    public const CATEGORIES = [
        // Income Categories
        'SALES' => 'Sales Revenue', // Revenue from sales
        'PARTNER_REPAYMENT' => 'Partner Loan Repayment', // Partner paying back personal loans
        'INVESTMENT_RETURN' => 'Investment Returns', // Returns from company investments
        'INVESTMENT_INCOME' => 'Investment Income', // External investments into company
        'OTHER_INCOME' => 'Other Income', // Other income sources

        // Expense Categories
        'RETURNS' => 'Returns & Refunds', // Real money refunds
        'PAY-PRODUCT' => 'Product Costs', // Product purchase costs
        'PAY-DELIVERY' => 'Delivery Costs', // Shipping costs
        'INVENTORY' => 'Inventory Value', // Current stock value
        'WITHDRAW' => 'Partner Withdrawals', // Partner withdrawals
        'BANK_FEE' => 'Banking Fees', // Banking fees and transfer commissions
        'BANK_COM' => 'Banking Fees', // Banking fees (deprecated - use BANK_FEE)
        'FEE' => 'Payment Fees', // Payment processor fees
        'ADS' => 'Advertising', // Advertising spend
        'OTHER_PAY' => 'Other Expenses', // All other expenses
    ];

    // Subcategories for more detailed tracking
    public const SUBCATEGORIES = [
        'PARTNER_REPAYMENT' => [
            'PERSONAL_LOAN' => 'Personal Loan Repayment',
            'ADVANCE_RETURN' => 'Advance Return',
            'DEBT_PAYMENT' => 'Debt Payment',
        ],
        'INVESTMENT_RETURN' => [
            'STOCK_DIVIDEND' => 'Stock Dividends',
            'FUND_RETURN' => 'Fund Returns',
            'CRYPTO_GAIN' => 'Crypto Gains',
            'INTEREST' => 'Interest Income',
        ],
        'INVESTMENT_INCOME' => [
            'ANGEL_INVESTMENT' => 'Angel Investment',
            'VC_FUNDING' => 'VC Funding',
            'PARTNER_INVESTMENT' => 'Partner Investment',
            'LOAN_RECEIVED' => 'Loan Received',
        ],
        'OTHER_INCOME' => [
            'REFUND' => 'Refund Received',
            'INSURANCE_CLAIM' => 'Insurance Claim',
            'TAX_REFUND' => 'Tax Refund',
            'MISC_INCOME' => 'Miscellaneous Income',
        ],
        'BANK_FEE' => [
            'TRANSFER_FEE' => 'Transfer Fee',
            'MONTHLY_FEE' => 'Monthly Account Fee',
            'EXCHANGE_FEE' => 'Currency Exchange Fee',
            'CARD_FEE' => 'Card Fee',
            'OTHER_FEE' => 'Other Bank Fee',
        ],
        'ADS' => [
            'FACEBOOK' => 'Facebook Ads',
            'GOOGLE' => 'Google Ads',
            'TIKTOK' => 'TikTok Ads',
            'INSTAGRAM' => 'Instagram Ads',
            'OTHER_ADS' => 'Other Advertising',
        ],
        'OTHER_PAY' => [
            'RENT' => 'Rent',
            'UTILITIES' => 'Utilities',
            'SALARY' => 'Salary',
            'PACKAGING' => 'Packaging',
            'SUBSCRIPTIONS' => 'Subscriptions',
            'MISC' => 'Miscellaneous',
        ],
    ];

    // Assignment status options
    public const ASSIGNMENT_PENDING = 'pending';

    public const ASSIGNMENT_ASSIGNED = 'assigned';

    public const ASSIGNMENT_SPLIT = 'split';

    public const ASSIGNMENT_MATCHED = 'matched';

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

    // Sales channels (WHERE sale happened)
    public const SALES_CHANNELS = [
        'shopify' => 'Shopify',
        'instagram' => 'Instagram',
        'telegram' => 'Telegram',
        'whatsapp' => 'WhatsApp',
        'facebook' => 'Facebook',
        'physical' => 'Physical Store',
        'referral' => 'Referral',
        'other' => 'Other',
    ];

    // Payment methods (HOW customer paid)
    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'credit_card' => 'Credit Card',
        'bank_transfer' => 'Bank Transfer',
        'cash_on_delivery' => 'Cash on Delivery',
        'cargo_collect' => 'Cargo Collect',
        'crypto' => 'Cryptocurrency',
        'installment' => 'Installment',
        'store_credit' => 'Store Credit',
        'other' => 'Other',
    ];

    // Data sources (FROM WHERE to system)
    public const DATA_SOURCES = [
        'shopify_api' => 'Shopify API',
        'stripe_api' => 'Stripe API',
        'paypal_api' => 'PayPal API',
        'manual_entry' => 'Manual Entry',
        'csv_import' => 'CSV Import',
        'webhook' => 'Webhook',
    ];

    // Boot method
    protected static function boot()
    {
        parent::boot();

        // Multi-tenant scoping
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                
                // Super admin can see all transactions
                if ($user->hasRole('super_admin')) {
                    return;
                }
                
                // Other users only see their company's transactions
                if ($user->company_id) {
                    $builder->whereHas('store', function ($query) use ($user) {
                        $query->where('company_id', $user->company_id);
                    });
                }
            }
        });

        static::creating(function ($transaction) {
            if (! $transaction->transaction_id) {
                $transaction->transaction_id = 'TXN-'.strtoupper(Str::random(8));
            }

            if (! $transaction->created_by && auth()->check()) {
                $transaction->created_by = auth()->id();
            }

            // Validate exchange rate for non-USD currencies
            if ($transaction->currency !== 'USD') {
                if (!$transaction->exchange_rate || $transaction->exchange_rate <= 0) {
                    throw new \InvalidArgumentException('Exchange rate must be greater than 0 for non-USD currencies');
                }
                
                // Calculate USD amount
                $transaction->amount_usd = round($transaction->amount * $transaction->exchange_rate, 2);
            } else {
                // USD transactions always have exchange rate of 1.0
                $transaction->amount_usd = $transaction->amount;
                $transaction->exchange_rate = 1.0;
            }
        });

        // Payment processor logic when transaction is approved
        static::updated(function ($transaction) {
            if ($transaction->wasChanged('status') && $transaction->status === 'APPROVED') {
                $transaction->handlePaymentProcessorLogic();
                
                // Create or link customer for sales transactions
                if ($transaction->category === 'SALES' && $transaction->type === 'income' && !$transaction->customer_id) {
                    $transaction->createOrLinkCustomer();
                }
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

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Transaction editor relationships
    public function matchedTransaction()
    {
        return $this->belongsTo(Transaction::class, 'matched_transaction_id');
    }

    public function matchingTransaction()
    {
        return $this->hasOne(Transaction::class, 'matched_transaction_id');
    }

    public function parentTransaction()
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }

    public function splitTransactions()
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id');
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

    // Helper to get income categories
    public static function getIncomeCategories(): array
    {
        return [
            'SALES' => 'Sales Revenue',
            'PARTNER_REPAYMENT' => 'Partner Loan Repayment',
            'INVESTMENT_RETURN' => 'Investment Returns',
            'INVESTMENT_INCOME' => 'Investment Income',
            'OTHER_INCOME' => 'Other Income',
        ];
    }

    // Helper to check if a category is income
    public static function isIncomeCategory(string $category): bool
    {
        return array_key_exists($category, self::getIncomeCategories());
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
        // Income categories that go through payment processors
        $processorIncomeCategories = ['SALES'];

        // SALES kategori → Payment processor'a pending balance ekle
        if (in_array($this->category, $processorIncomeCategories) && $this->type === self::TYPE_INCOME) {
            $this->addToPendingBalance();
        }

        // Partner repayment tracking
        if ($this->category === 'PARTNER_REPAYMENT' && $this->partner_id) {
            $this->updatePartnerDebtRepayment();
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
            'currency' => $this->currency,
        ], [
            'current_balance' => 0,
            'pending_balance' => 0,
            'is_active' => true,
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
        if (! $this->partner_id || ! $this->store_id) {
            return;
        }

        // Find the partnership for this partner and store
        $partnership = Partnership::where('user_id', $this->partner_id)
            ->where('store_id', $this->store_id)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $partnership) {
            \Log::warning('No active partnership found for debt tracking', [
                'transaction_id' => $this->transaction_id,
                'partner_id' => $this->partner_id,
                'store_id' => $this->store_id,
            ]);

            return;
        }

        // Add the expense amount to partner's debt (positive amount increases debt)
        $partnership->addDebt(
            abs($this->amount),
            "Personal expense: {$this->description}"
        );
    }

    private function updatePartnerDebtRepayment(): void
    {
        if (! $this->partner_id || ! $this->store_id) {
            return;
        }

        // Find the partnership for this partner and store
        $partnership = Partnership::where('user_id', $this->partner_id)
            ->where('store_id', $this->store_id)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $partnership) {
            \Log::warning('No active partnership found for debt repayment', [
                'transaction_id' => $this->transaction_id,
                'partner_id' => $this->partner_id,
                'store_id' => $this->store_id,
            ]);

            return;
        }

        // Reduce the debt by the repayment amount
        $partnership->reduceDebt(
            abs($this->amount),
            "Debt repayment: {$this->description}"
        );
    }

    /**
     * Payout process - Manuel olarak çağrılacak
     */
    public function processPayoutToBank(int $bankAccountId): void
    {
        if (! $this->paymentProcessor) {
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
            'payout_date' => now(),
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
            self::PROCESSOR_MANUAL => 'Manual Entry',
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

        // Revenue (all income categories)
        $incomeCategories = array_keys(self::getIncomeCategories());
        $revenue = $query->clone()->whereIn('category', $incomeCategories)->sum('amount_usd');

        // Expenses (all non-income categories)
        $expenses = $query->clone()
            ->whereNotIn('category', $incomeCategories)
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
                'label' => $label,
            ];
        }

        return $totals;
    }
    
    /**
     * Create or link customer from transaction data
     */
    public function createOrLinkCustomer(): void
    {
        if (!$this->customer_info || $this->customer_id) {
            return;
        }
        
        $customerData = is_string($this->customer_info) 
            ? json_decode($this->customer_info, true) 
            : $this->customer_info;
            
        if (!$customerData || (!isset($customerData['phone']) && !isset($customerData['email']))) {
            return;
        }
        
        // Find or create customer
        $customer = Customer::findOrCreateFromData([
            'name' => $customerData['name'] ?? null,
            'phone' => $customerData['phone'] ?? null,
            'email' => $customerData['email'] ?? null,
            'whatsapp_number' => $customerData['whatsapp'] ?? null,
            'source' => 'manual',
            'notes' => $customerData['notes'] ?? null,
        ], $this->store);
        
        if ($customer) {
            $this->customer_id = $customer->id;
            $this->saveQuietly(); // Don't trigger events again
            
            // Log timeline event
            $customer->logTimelineEvent(
                'order_placed',
                'Sipariş alındı',
                "Tutar: {$this->amount} {$this->currency}",
                [
                    'amount' => $this->amount,
                    'currency' => $this->currency,
                    'payment_method' => $this->payment_method,
                    'sales_channel' => $this->sales_channel,
                ],
                'Transaction',
                $this->id
            );
            
            // Update customer statistics
            $customer->updateStatistics();
        }
    }
}
