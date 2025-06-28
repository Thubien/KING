<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'bank_type',
        'account_name',
        'account_number',
        'routing_number',
        'iban',
        'swift_code',
        'currency',
        'current_balance',
        'is_primary',
        'is_active',
        'metadata',
        'last_sync_at',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'last_sync_at' => 'datetime',
    ];

    // Bank types
    const TYPE_MERCURY = 'MERCURY';
    const TYPE_PAYONEER = 'PAYONEER';
    const TYPE_CHASE = 'CHASE';
    const TYPE_WELLS_FARGO = 'WELLS_FARGO';
    const TYPE_BANK_OF_AMERICA = 'BANK_OF_AMERICA';
    const TYPE_OTHER = 'OTHER';

    // Global scope for multi-tenancy
    protected static function booted()
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('company_id', auth()->user()->company_id);
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

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('bank_type', $type);
    }

    // Business Logic Methods
    public function addBalance(float $amount, string $description = null): void
    {
        $this->increment('current_balance', $amount);
        
        $this->logBalanceChange('added', $amount, $description);
    }

    public function subtractBalance(float $amount, string $description = null): void
    {
        if ($amount > $this->current_balance) {
            throw new \InvalidArgumentException('Insufficient balance');
        }

        $this->decrement('current_balance', $amount);
        
        $this->logBalanceChange('subtracted', $amount, $description);
    }

    private function logBalanceChange(string $type, float $amount, string $description = null): void
    {
        \Log::info('Bank Account Balance Change', [
            'bank_account_id' => $this->id,
            'bank_type' => $this->bank_type,
            'change_type' => $type,
            'amount' => $amount,
            'description' => $description,
            'current_balance' => $this->current_balance,
            'timestamp' => now()
        ]);
    }

    // Encryption for sensitive data
    public function setAccountNumberAttribute($value)
    {
        $this->attributes['account_number'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccountNumberAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setRoutingNumberAttribute($value)
    {
        $this->attributes['routing_number'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRoutingNumberAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    // Display helpers
    public function getDisplayName(): string
    {
        return $this->account_name ?: $this->getBankTypeName() . ' Account';
    }

    public function getBankTypeName(): string
    {
        $names = [
            self::TYPE_MERCURY => 'Mercury Bank',
            self::TYPE_PAYONEER => 'Payoneer',
            self::TYPE_CHASE => 'Chase Bank',
            self::TYPE_WELLS_FARGO => 'Wells Fargo',
            self::TYPE_BANK_OF_AMERICA => 'Bank of America',
            self::TYPE_OTHER => 'Other Bank'
        ];

        return $names[$this->bank_type] ?? $this->bank_type;
    }

    public function getFormattedBalance(): string
    {
        return number_format($this->current_balance, 2) . ' ' . $this->currency;
    }

    public function getMaskedAccountNumber(): string
    {
        if (!$this->account_number) return 'N/A';
        
        $decrypted = $this->account_number;
        return '****' . substr($decrypted, -4);
    }

    // Static helper methods
    public static function createDefault(int $companyId, string $currency = 'USD'): self
    {
        return self::create([
            'company_id' => $companyId,
            'bank_type' => self::TYPE_OTHER,
            'account_name' => 'Primary Account',
            'currency' => $currency,
            'current_balance' => 0,
            'is_primary' => true,
            'is_active' => true
        ]);
    }

    public static function getPrimaryAccount(int $companyId): ?self
    {
        return self::where('company_id', $companyId)
            ->where('is_primary', true)
            ->where('is_active', true)
            ->first();
    }

    // Validation
    public function validateBalance(): bool
    {
        return $this->current_balance >= 0;
    }
}