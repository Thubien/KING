<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'bank_type',
        'bank_name',
        'bank_branch',
        'country_code',
        'bank_address',
        'bank_phone',
        'bank_website',
        'account_name',
        'account_number',
        'routing_number',
        'iban',
        'swift_code',
        'bic_code',
        'sort_code',
        'bsb_number',
        'institution_number',
        'bank_code',
        'currency',
        'current_balance',
        'is_primary',
        'is_active',
        'metadata',
        'custom_fields',
        'last_sync_at',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'custom_fields' => 'array',
        'last_sync_at' => 'datetime',
    ];

    // Common bank types (for suggestions, not restrictions)
    const SUGGESTED_TYPES = [
        'commercial' => 'Commercial Bank',
        'credit_union' => 'Credit Union',
        'online' => 'Online Bank',
        'investment' => 'Investment Bank',
        'savings' => 'Savings & Loan',
        'other' => 'Other Institution',
    ];

    // Popular international banks (for autocomplete)
    const POPULAR_BANKS = [
        'US' => ['Chase Bank', 'Wells Fargo', 'Bank of America', 'Citibank', 'US Bank', 'PNC Bank', 'Capital One'],
        'UK' => ['Barclays', 'HSBC', 'Lloyds Bank', 'NatWest', 'Santander UK', 'TSB Bank'],
        'CA' => ['Royal Bank of Canada', 'Toronto-Dominion Bank', 'Bank of Nova Scotia', 'Bank of Montreal'],
        'AU' => ['Commonwealth Bank', 'Westpac', 'ANZ Bank', 'NAB'],
        'DE' => ['Deutsche Bank', 'Commerzbank', 'DZ Bank', 'Sparkasse'],
        'FR' => ['BNP Paribas', 'Crédit Agricole', 'Société Générale', 'BPCE'],
        'TR' => ['Ziraat Bankası', 'İş Bankası', 'Garanti BBVA', 'Akbank', 'Yapı Kredi'],
        'UA' => ['PrivatBank', 'Oschadbank', 'Raiffeisen Bank Aval', 'PUMB', 'Alfa-Bank Ukraine', 'UkrSibbank', 'Crédit Agricole Ukraine'],
    ];

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
    public function addBalance(float $amount, ?string $description = null): void
    {
        $this->increment('current_balance', $amount);

        $this->logBalanceChange('added', $amount, $description);
    }

    public function subtractBalance(float $amount, ?string $description = null): void
    {
        if ($amount > $this->current_balance) {
            throw new \InvalidArgumentException('Insufficient balance');
        }

        $this->decrement('current_balance', $amount);

        $this->logBalanceChange('subtracted', $amount, $description);
    }

    public function logBalanceChange(string $type, float $amount, ?string $description = null): void
    {
        \Log::info('Bank Account Balance Change', [
            'bank_account_id' => $this->id,
            'bank_type' => $this->bank_type,
            'change_type' => $type,
            'amount' => $amount,
            'description' => $description,
            'current_balance' => $this->current_balance,
            'timestamp' => now(),
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
        if ($this->account_name) {
            return $this->account_name;
        }

        if ($this->bank_name) {
            return $this->bank_name.' Account';
        }

        return $this->getBankTypeName().' Account';
    }

    public function getBankTypeName(): string
    {
        // If it's a suggested type, return the friendly name
        if (isset(self::SUGGESTED_TYPES[$this->bank_type])) {
            return self::SUGGESTED_TYPES[$this->bank_type];
        }

        // If bank_name is provided, use that
        if ($this->bank_name) {
            return $this->bank_name;
        }

        // Otherwise return the bank_type as-is (custom input)
        return $this->bank_type ?: 'Unknown Bank';
    }

    public function getFullBankInfo(): string
    {
        $parts = [];

        if ($this->bank_name) {
            $parts[] = $this->bank_name;
        }

        if ($this->bank_branch) {
            $parts[] = $this->bank_branch;
        }

        if ($this->country_code && $this->country_code !== 'US') {
            $parts[] = strtoupper($this->country_code);
        }

        return implode(' - ', $parts) ?: $this->getBankTypeName();
    }

    public function getFormattedBalance(): string
    {
        return number_format($this->current_balance, 2).' '.$this->currency;
    }

    public function getMaskedAccountNumber(): string
    {
        if (! $this->account_number) {
            return 'N/A';
        }

        $decrypted = $this->account_number;

        return '****'.substr($decrypted, -4);
    }

    // Static helper methods
    public static function createDefault(int $companyId, string $currency = 'USD'): self
    {
        return self::create([
            'company_id' => $companyId,
            'bank_type' => 'other',
            'account_name' => 'Primary Account',
            'currency' => $currency,
            'current_balance' => 0,
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    public static function getPopularBanksForCountry(string $countryCode): array
    {
        return self::POPULAR_BANKS[strtoupper($countryCode)] ?? [];
    }

    public static function getSuggestedTypes(): array
    {
        return self::SUGGESTED_TYPES;
    }

    public static function getCountrySpecificFields(string $countryCode): array
    {
        $fields = [];

        switch (strtoupper($countryCode)) {
            case 'US':
                $fields = ['routing_number' => 'Routing Number (9 digits)'];
                break;
            case 'UK':
                $fields = ['sort_code' => 'Sort Code (6 digits)', 'account_number' => 'Account Number'];
                break;
            case 'AU':
                $fields = ['bsb_number' => 'BSB Number (6 digits)', 'account_number' => 'Account Number'];
                break;
            case 'CA':
                $fields = ['institution_number' => 'Institution Number', 'routing_number' => 'Transit Number'];
                break;
            case 'DE':
            case 'FR':
            case 'ES':
            case 'IT':
                $fields = ['iban' => 'IBAN', 'bic_code' => 'BIC/SWIFT Code'];
                break;
            case 'TR':
                $fields = ['iban' => 'IBAN (TR + 24 digits)', 'bank_code' => 'Bank Code'];
                break;
            case 'UA':
                $fields = [
                    'iban' => 'IBAN (UA + 27 digits)',
                    'bank_code' => 'MFO Code (6 digits)',
                    'account_number' => 'Account Number (14 digits)',
                ];
                break;
            default:
                $fields = ['iban' => 'IBAN (if available)', 'swift_code' => 'SWIFT/BIC Code'];
        }

        return $fields;
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
