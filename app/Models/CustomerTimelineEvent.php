<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerTimelineEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'event_type',
        'event_title',
        'event_description',
        'event_data',
        'related_model',
        'related_id',
        'created_by',
    ];

    protected $casts = [
        'event_data' => 'array',
        'created_at' => 'datetime',
    ];

    // Event type constants
    const TYPE_ORDER_PLACED = 'order_placed';
    const TYPE_ORDER_COMPLETED = 'order_completed';
    const TYPE_ORDER_CANCELLED = 'order_cancelled';
    const TYPE_RETURN_REQUESTED = 'return_requested';
    const TYPE_RETURN_COMPLETED = 'return_completed';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';
    const TYPE_STORE_CREDIT_ISSUED = 'store_credit_issued';
    const TYPE_STORE_CREDIT_USED = 'store_credit_used';
    const TYPE_NOTE_ADDED = 'note_added';
    const TYPE_TAG_ADDED = 'tag_added';
    const TYPE_TAG_REMOVED = 'tag_removed';
    const TYPE_STATUS_CHANGED = 'status_changed';
    const TYPE_COMMUNICATION_SENT = 'communication_sent';
    const TYPE_CUSTOMER_CREATED = 'customer_created';
    const TYPE_ADDRESS_ADDED = 'address_added';
    const TYPE_ADDRESS_UPDATED = 'address_updated';

    // Event icons and colors
    const EVENT_CONFIG = [
        'order_placed' => ['icon' => 'shopping-cart', 'color' => 'green'],
        'order_completed' => ['icon' => 'check-circle', 'color' => 'green'],
        'order_cancelled' => ['icon' => 'x-circle', 'color' => 'red'],
        'return_requested' => ['icon' => 'arrow-uturn-left', 'color' => 'orange'],
        'return_completed' => ['icon' => 'arrow-uturn-left', 'color' => 'red'],
        'payment_received' => ['icon' => 'banknotes', 'color' => 'green'],
        'store_credit_issued' => ['icon' => 'gift', 'color' => 'purple'],
        'store_credit_used' => ['icon' => 'gift', 'color' => 'purple'],
        'note_added' => ['icon' => 'chat-bubble-left-right', 'color' => 'blue'],
        'tag_added' => ['icon' => 'tag', 'color' => 'indigo'],
        'tag_removed' => ['icon' => 'tag', 'color' => 'gray'],
        'status_changed' => ['icon' => 'arrow-path', 'color' => 'yellow'],
        'communication_sent' => ['icon' => 'envelope', 'color' => 'blue'],
        'customer_created' => ['icon' => 'user-plus', 'color' => 'green'],
        'address_added' => ['icon' => 'map-pin', 'color' => 'blue'],
        'address_updated' => ['icon' => 'map-pin', 'color' => 'yellow'],
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper Methods
    public function getIcon(): string
    {
        return self::EVENT_CONFIG[$this->event_type]['icon'] ?? 'information-circle';
    }

    public function getColor(): string
    {
        return self::EVENT_CONFIG[$this->event_type]['color'] ?? 'gray';
    }

    public function getRelatedRecord()
    {
        if (!$this->related_model || !$this->related_id) {
            return null;
        }

        $modelClass = "App\\Models\\{$this->related_model}";
        
        if (!class_exists($modelClass)) {
            return null;
        }

        return $modelClass::find($this->related_id);
    }

    public function getFormattedEventDataAttribute(): array
    {
        $data = $this->event_data ?? [];
        
        // Format common data types
        if (isset($data['amount'])) {
            $data['formatted_amount'] = number_format($data['amount'], 2) . ' ' . ($data['currency'] ?? 'TRY');
        }
        
        if (isset($data['old_value']) && isset($data['new_value'])) {
            $data['change'] = "{$data['old_value']} → {$data['new_value']}";
        }
        
        return $data;
    }

    // Scopes
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeImportant($query)
    {
        $importantTypes = [
            self::TYPE_ORDER_PLACED,
            self::TYPE_RETURN_REQUESTED,
            self::TYPE_PAYMENT_RECEIVED,
            self::TYPE_STATUS_CHANGED,
        ];
        
        return $query->whereIn('event_type', $importantTypes);
    }
}