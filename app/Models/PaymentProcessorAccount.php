<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProcessorAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'processor_type',
        'account_identifier',
        'currency',
        'current_balance',
        'pending_balance',
        'pending_payouts',
        'metadata',
        'last_sync_at',
        'is_active',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'pending_payouts' => 'decimal:2',
        'metadata' => 'array',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Processor types
    const TYPE_STRIPE = 'STRIPE';

    const TYPE_PAYPAL = 'PAYPAL';

    const TYPE_SHOPIFY_PAYMENTS = 'SHOPIFY_PAYMENTS';

    const TYPE_MANUAL = 'MANUAL'; // Manuel CSV girişleri için

    // Global scope for multi-tenancy
    protected static function booted()
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                
                // Super admin can see all payment processor accounts
                if ($user->hasRole('super_admin')) {
                    return;
                }
                
                // Other users only see their company's payment processor accounts
                if ($user->company_id) {
                    $builder->where('company_id', $user->company_id);
                }
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('processor_type', $type);
    }

    public function scopeStripe($query)
    {
        return $query->where('processor_type', self::TYPE_STRIPE);
    }

    public function scopePaypal($query)
    {
        return $query->where('processor_type', self::TYPE_PAYPAL);
    }

    public function scopeShopifyPayments($query)
    {
        return $query->where('processor_type', self::TYPE_SHOPIFY_PAYMENTS);
    }

    // Business Logic Methods
    public function getTotalBalance(): float
    {
        return $this->current_balance + $this->pending_balance;
    }

    public function getAvailableBalance(): float
    {
        return $this->current_balance;
    }

    public function addPendingBalance(float $amount, ?string $description = null): void
    {
        $this->increment('pending_balance', $amount);

        $this->logBalanceChange('pending_added', $amount, $description);
    }

    public function movePendingToCurrent(float $amount, ?string $description = null): void
    {
        if ($amount > $this->pending_balance) {
            throw new \InvalidArgumentException('Amount exceeds pending balance');
        }

        $this->decrement('pending_balance', $amount);
        $this->increment('current_balance', $amount);

        $this->logBalanceChange('pending_to_current', $amount, $description);
    }

    public function addCurrentBalance(float $amount, ?string $description = null): void
    {
        $this->increment('current_balance', $amount);

        $this->logBalanceChange('current_added', $amount, $description);
    }

    public function withdrawCurrentBalance(float $amount, ?string $description = null): void
    {
        if ($amount > $this->current_balance) {
            throw new \InvalidArgumentException('Amount exceeds current balance');
        }

        $this->decrement('current_balance', $amount);

        $this->logBalanceChange('current_withdrawn', $amount, $description);
    }

    private function logBalanceChange(string $type, float $amount, ?string $description = null): void
    {
        // Log for audit trail
        \Log::info('Payment Processor Balance Change', [
            'processor_account_id' => $this->id,
            'processor_type' => $this->processor_type,
            'change_type' => $type,
            'amount' => $amount,
            'description' => $description,
            'current_balance' => $this->current_balance,
            'pending_balance' => $this->pending_balance,
            'timestamp' => now(),
        ]);
    }

    // Validation Methods
    public function validateBalance(): bool
    {
        return $this->current_balance >= 0 && $this->pending_balance >= 0;
    }

    // Helper Methods
    public static function getCompanyTotalRealMoney(int $companyId): float
    {
        $bankTotal = BankAccount::where('company_id', $companyId)->sum('current_balance');
        $processorTotal = self::where('company_id', $companyId)
            ->sum(\DB::raw('current_balance + pending_balance'));

        return $bankTotal + $processorTotal;
    }

    public static function createDefault(int $companyId, string $currency = 'USD'): array
    {
        $processors = [
            self::TYPE_STRIPE,
            self::TYPE_PAYPAL,
            self::TYPE_SHOPIFY_PAYMENTS,
            self::TYPE_MANUAL,
        ];

        $accounts = [];
        foreach ($processors as $type) {
            $accounts[] = self::create([
                'company_id' => $companyId,
                'processor_type' => $type,
                'currency' => $currency,
                'current_balance' => 0,
                'pending_balance' => 0,
                'is_active' => true,
            ]);
        }

        return $accounts;
    }

    // Display helpers
    public function getDisplayName(): string
    {
        $names = [
            self::TYPE_STRIPE => 'Stripe',
            self::TYPE_PAYPAL => 'PayPal',
            self::TYPE_SHOPIFY_PAYMENTS => 'Shopify Payments',
            self::TYPE_MANUAL => 'Manual Entry',
        ];

        return $names[$this->processor_type] ?? $this->processor_type;
    }

    public function getFormattedCurrentBalance(): string
    {
        return number_format($this->current_balance, 2).' '.$this->currency;
    }

    public function getFormattedPendingBalance(): string
    {
        return number_format($this->pending_balance, 2).' '.$this->currency;
    }

    public function getFormattedTotalBalance(): string
    {
        return number_format($this->getTotalBalance(), 2).' '.$this->currency;
    }
}
