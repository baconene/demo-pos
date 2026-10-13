<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParcelItem extends Model
{
    protected $fillable = ['parcel_id', 'item_name', 'quantity', 'status', 'status_updated_at'];

    protected $casts = [
        'status_updated_at' => 'datetime',
        'quantity'          => 'integer',
    ];

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }
}
