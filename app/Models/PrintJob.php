<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintJob extends Model
{
    protected $fillable = [
        'order_id', 'trigger', 'trigger_status', 'status',
        'receipt_data', 'attempts', 'failed_reason', 'sent_at', 'printed_at',
    ];

    protected $casts = [
        'receipt_data' => 'array',
        'sent_at'      => 'datetime',
        'printed_at'   => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
