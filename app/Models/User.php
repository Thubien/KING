<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'user_type',
        'preferences',
        'last_login_at',
        'avatar_url',
        'is_active',
        'avatar',
        'title',
        'phone',
        'bio',
        'language',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function partnerships(): HasMany
    {
        return $this->hasMany(Partnership::class);
    }

    public function createdTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(UserLoginLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Business Logic Methods
    public function isCompanyOwner(): bool
    {
        return $this->user_type === 'company_owner';
    }

    public function isPartner(): bool
    {
        return $this->user_type === 'partner';
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    public function isSalesRep(): bool
    {
        return $this->hasRole('sales_rep');
    }

    public function canCreateOrders(): bool
    {
        return $this->can('create_manual_orders');
    }

    public function getActivePartnerships()
    {
        return Cache::remember(
            "user:{$this->id}:active_partnerships",
            now()->addMinutes(5),
            function () {
                return $this->partnerships()
                    ->active()
                    ->with(['store' => function ($query) {
                        $query->select('id', 'name', 'company_id', 'currency', 'status');
                    }])
                    ->get();
            }
        );
    }

    public function getTotalOwnershipPercentage(): float
    {
        return $this->partnerships()->active()->sum('ownership_percentage');
    }

    public function hasStoreAccess(int $storeId): bool
    {
        if ($this->isAdmin() || $this->isCompanyOwner()) {
            return true;
        }

        return $this->partnerships()
            ->where('store_id', $storeId)
            ->where('status', 'ACTIVE')
            ->exists();
    }

    public function getAccessibleStoreIds(): array
    {
        return Cache::remember(
            "user:{$this->id}:accessible_stores",
            now()->addMinutes(10),
            function () {
                if ($this->isAdmin() || $this->isCompanyOwner()) {
                    return $this->company->stores()->pluck('id')->toArray();
                }

                return $this->partnerships()
                    ->where('status', 'ACTIVE')
                    ->pluck('store_id')
                    ->toArray();
            }
        );
    }

    public function getTotalMonthlyProfitShare(): float
    {
        $currentMonth = now()->startOfMonth();
        $nextMonth = now()->addMonth()->startOfMonth();

        return $this->partnerships()
            ->where('status', 'ACTIVE')
            ->whereHas('store.transactions', function ($query) use ($currentMonth, $nextMonth) {
                $query->whereBetween('created_at', [$currentMonth, $nextMonth])
                    ->where('category', 'revenue');
            })
            ->with(['store.transactions' => function ($query) use ($currentMonth, $nextMonth) {
                $query->whereBetween('created_at', [$currentMonth, $nextMonth])
                    ->where('category', 'revenue');
            }])
            ->get()
            ->sum(function ($partnership) {
                $storeRevenue = $partnership->store->transactions->sum('amount');

                return $storeRevenue * ($partnership->ownership_percentage / 100);
            });
    }

    public function assignRoleBasedOnUserType(): void
    {
        $this->syncRoles([]); // Clear existing roles

        switch ($this->user_type) {
            case 'company_owner':
                $this->assignRole('company_owner');
                break;
            case 'partner':
                $this->assignRole('partner');
                break;
            case 'staff':
                $this->assignRole('staff');
                break;
        }
    }

    /**
     * Clear user-specific cache when partnerships change
     */
    public function clearPartnershipCache(): void
    {
        Cache::forget("user:{$this->id}:accessible_stores");
        Cache::forget("user:{$this->id}:active_partnerships");
    }

    /**
     * Get cached total ownership percentage
     */
    public function getCachedTotalOwnership(): float
    {
        return Cache::remember(
            "user:{$this->id}:total_ownership",
            now()->addMinutes(30),
            fn () => $this->partnerships()->active()->sum('ownership_percentage')
        );
    }

    //  Sales Rep Commission Methods

    /**
     * Get sales rep transactions relationship
     */
    public function salesTransactions()
    {
        return $this->hasMany(Transaction::class, 'sales_rep_id');
    }

    /**
     * Get monthly sales for sales rep
     */
    public function getMonthlySales(?string $month = null): float
    {
        $month = $month ?? now()->format('Y-m');

        return $this->salesTransactions()
            ->where('type', 'INCOME')
            ->where('status', 'APPROVED')
            ->where('data_source', 'manual_entry')
            ->whereYear('transaction_date', '=', substr($month, 0, 4))
            ->whereMonth('transaction_date', '=', substr($month, 5, 2))
            ->sum('amount_usd');
    }

    /**
     * Get commission rate from partnership
     */
    public function getCommissionRate(?int $storeId = null): float
    {
        if (! $storeId) {
            // Default commission rate if no specific store
            return 10.0; // 10% default
        }

        $partnership = $this->partnerships()
            ->where('store_id', $storeId)
            ->where('status', 'ACTIVE')
            ->first();

        return $partnership ? $partnership->ownership_percentage : 10.0;
    }

    /**
     * Calculate monthly commission earnings
     */
    public function getMonthlyCommission(?string $month = null, ?int $storeId = null): float
    {
        $month = $month ?? now()->format('Y-m');
        $sales = $this->getMonthlySales($month);

        if ($storeId) {
            $commissionRate = $this->getCommissionRate($storeId);
        } else {
            // Average commission rate across all stores
            $commissionRate = $this->partnerships()
                ->active()
                ->avg('ownership_percentage') ?? 10.0;
        }

        return $sales * ($commissionRate / 100);
    }

    /**
     * Get sales rep performance stats
     */
    public function getSalesRepStats(): array
    {
        $currentMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');

        $currentSales = $this->getMonthlySales($currentMonth);
        $lastMonthSales = $this->getMonthlySales($lastMonth);

        $growth = $lastMonthSales > 0
            ? (($currentSales - $lastMonthSales) / $lastMonthSales) * 100
            : 0;

        return [
            'current_month_sales' => $currentSales,
            'last_month_sales' => $lastMonthSales,
            'growth_percentage' => round($growth, 2),
            'total_orders' => $this->salesTransactions()
                ->where('type', 'INCOME')
                ->where('data_source', 'manual_entry')
                ->whereYear('transaction_date', '=', substr($currentMonth, 0, 4))
                ->whereMonth('transaction_date', '=', substr($currentMonth, 5, 2))
                ->count(),
            'avg_order_value' => $this->salesTransactions()
                ->where('type', 'INCOME')
                ->where('data_source', 'manual_entry')
                ->whereYear('transaction_date', '=', substr($currentMonth, 0, 4))
                ->whereMonth('transaction_date', '=', substr($currentMonth, 5, 2))
                ->avg('amount_usd') ?? 0,
            'commission_earned' => $this->getMonthlyCommission($currentMonth),
        ];
    }

    /**
     * Get customer acquisition stats for sales rep
     */
    public function getCustomerStats(): array
    {
        $transactions = $this->salesTransactions()
            ->where('type', 'INCOME')
            ->where('data_source', 'manual_entry')
            ->whereNotNull('customer_info')
            ->get();

        $uniqueCustomers = $transactions
            ->pluck('customer_info')
            ->filter()
            ->unique('name')
            ->count();

        $repeatCustomers = $transactions
            ->groupBy('customer_info.name')
            ->filter(fn ($group) => $group->count() > 1)
            ->count();

        return [
            'total_customers' => $uniqueCustomers,
            'repeat_customers' => $repeatCustomers,
            'repeat_rate' => $uniqueCustomers > 0
                ? round(($repeatCustomers / $uniqueCustomers) * 100, 2)
                : 0,
        ];
    }

    /**
     * Check if user can access sales rep dashboard
     */
    public function canAccessSalesRepDashboard(): bool
    {
        return $this->isSalesRep() || $this->isAdmin() || $this->isCompanyOwner();
    }

    /**
     * Get or create user settings
     */
    public function getSettings(): UserSetting
    {
        return $this->settings()->firstOrCreate(
            ['user_id' => $this->id],
            UserSetting::make(['user_id' => $this->id])->attributesToArray()
        );
    }

    /**
     * Get formatted date according to user preference
     */
    public function formatDate($date): string
    {
        if (!$date) return '';
        
        $format = $this->getSettings()->date_format ?? 'd/m/Y';
        return $date instanceof \DateTime ? $date->format($format) : \Carbon\Carbon::parse($date)->format($format);
    }

    /**
     * Get formatted time according to user preference
     */
    public function formatTime($time): string
    {
        if (!$time) return '';
        
        $format = $this->getSettings()->time_format ?? 'H:i';
        return $time instanceof \DateTime ? $time->format($format) : \Carbon\Carbon::parse($time)->format($format);
    }

    /**
     * Get formatted datetime according to user preference
     */
    public function formatDateTime($datetime): string
    {
        if (!$datetime) return '';
        
        $settings = $this->getSettings();
        $format = $settings->date_format . ' ' . $settings->time_format;
        return $datetime instanceof \DateTime ? $datetime->format($format) : \Carbon\Carbon::parse($datetime)->format($format);
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return \Storage::url($this->avatar);
        }

        // Generate Gravatar URL as fallback
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
    }
}
