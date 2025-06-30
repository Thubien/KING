<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    protected $fillable = [
        'company_id',
        'store_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'product_name',
        'product_sku',
        'quantity',
        'refund_amount',
        'currency',
        'return_reason',
        'status',
        'resolution',
        'notes',
        'tracking_number',
        'customer_tracking_number',
        'handled_by',
        'media',
    ];

    protected $casts = [
        'media' => 'array',
        'refund_amount' => 'decimal:2',
        'quantity' => 'integer',
    ];

    const STATUSES = [
        'pending' => 'Beklemede',
        'in_transit' => 'Yolda',
        'processing' => 'İşlemde',
        'completed' => 'Tamamlandı',
    ];

    const RESOLUTIONS = [
        'refund' => 'Para İadesi',
        'exchange' => 'Değişim',
        'store_credit' => 'Mağaza Kredisi',
        'rejected' => 'Reddedildi',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function checklists()
    {
        return $this->hasMany(ReturnChecklist::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getStatusColorAttribute()
    {
        return [
            'pending' => 'gray',
            'in_transit' => 'yellow',
            'processing' => 'blue',
            'completed' => 'green',
        ][$this->status] ?? 'gray';
    }

    public function getCompletionPercentageAttribute()
    {
        $total = $this->checklists->where('stage', $this->status)->count();
        $checked = $this->checklists->where('stage', $this->status)->where('is_checked', true)->count();

        return $total > 0 ? round(($checked / $total) * 100) : 0;
    }

    public function getStatusLabelAttribute()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getResolutionLabelAttribute()
    {
        return self::RESOLUTIONS[$this->resolution] ?? $this->resolution;
    }
}
