<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

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

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    public function getActivePartnerships()
    {
        return $this->partnerships()->active()->with('store')->get();
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
            ->where('status', 'active')
            ->exists();
    }
}
