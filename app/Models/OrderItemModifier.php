<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemModifier extends Model
{
    protected $fillable = ['order_item_id', 'modifier_id', 'price'];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function modifier()
    {
        return $this->belongsTo(Modifier::class);
    }
}
