<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnChecklist extends Model
{
    protected $fillable = [
        'return_request_id',
        'stage',
        'item_text',
        'is_checked',
        'checked_at',
        'checked_by',
    ];
    
    protected $casts = [
        'is_checked' => 'boolean',
        'checked_at' => 'datetime',
    ];
    
    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class);
    }
    
    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
