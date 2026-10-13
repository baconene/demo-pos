<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionSnapshotDetail extends Model
{
    protected $fillable = [
        'snapshot_id', 'recipient_type', 'shareholder_id', 'recipient_name', 'percentage', 'amount',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'amount'     => 'decimal:2',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(DistributionSnapshot::class, 'snapshot_id');
    }

    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class);
    }
}
