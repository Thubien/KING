<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'partnership_id',
        'initiated_by_user_id',
        'approved_by_user_id',
        'settlement_type',
        'amount',
        'currency',
        'description',
        'status',
        'payment_method',
        'payment_reference',
        'settled_at',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'debt_balance_before',
        'debt_balance_after',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'debt_balance_before' => 'decimal:2',
        'debt_balance_after' => 'decimal:2',
        'settled_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    // Settlement type constants
    const TYPE_PAYMENT = 'payment';           // Partner pays back debt

    const TYPE_WITHDRAWAL = 'withdrawal';     // Partner withdraws profit (creates debt)

    const TYPE_EXPENSE = 'expense';           // Personal expense settlement

    const TYPE_ADJUSTMENT = 'adjustment';     // Manual adjustment

    const TYPE_PROFIT_SHARE = 'profit_share'; // Profit distribution

    // Relationships
    public function partnership(): BelongsTo
    {
        return $this->belongsTo(Partnership::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    // Business logic
    public function approve(User $approver, ?string $paymentReference = null): void
    {
        if (! $this->canBeApproved()) {
            throw new \Exception('Settlement cannot be approved in current status.');
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by_user_id' => $approver->id,
            'approved_at' => now(),
            'payment_reference' => $paymentReference,
        ]);

        // Update partnership debt balance
        if ($this->settlement_type === self::TYPE_PAYMENT) {
            $this->partnership->reduceDebt($this->amount, "Settlement #{$this->id} approved");
        } elseif (in_array($this->settlement_type, [self::TYPE_WITHDRAWAL, self::TYPE_EXPENSE])) {
            $this->partnership->addDebt($this->amount, "Settlement #{$this->id} approved");
        }

        $this->update([
            'debt_balance_after' => $this->partnership->debt_balance,
        ]);
    }

    public function reject(User $rejecter, string $reason): void
    {
        if (! $this->canBeApproved()) {
            throw new \Exception('Settlement cannot be rejected in current status.');
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function markAsCompleted(): void
    {
        if ($this->status !== self::STATUS_APPROVED) {
            throw new \Exception('Only approved settlements can be marked as completed.');
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'settled_at' => now(),
        ]);
    }

    // Static helpers
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_PAYMENT => 'Payment (Reduce Debt)',
            self::TYPE_WITHDRAWAL => 'Withdrawal (Increase Debt)',
            self::TYPE_EXPENSE => 'Personal Expense',
            self::TYPE_ADJUSTMENT => 'Manual Adjustment',
            self::TYPE_PROFIT_SHARE => 'Profit Distribution',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function getFormattedAmount(): string
    {
        $prefix = '';
        if ($this->settlement_type === self::TYPE_PAYMENT) {
            $prefix = '-'; // Reducing debt
        } elseif (in_array($this->settlement_type, [self::TYPE_WITHDRAWAL, self::TYPE_EXPENSE])) {
            $prefix = '+'; // Increasing debt
        }

        return $prefix.$this->currency.' '.number_format($this->amount, 2);
    }

    public function getTypeColor(): string
    {
        return match ($this->settlement_type) {
            self::TYPE_PAYMENT => 'success',
            self::TYPE_WITHDRAWAL => 'warning',
            self::TYPE_EXPENSE => 'danger',
            self::TYPE_ADJUSTMENT => 'info',
            self::TYPE_PROFIT_SHARE => 'primary',
            default => 'gray',
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }
}
