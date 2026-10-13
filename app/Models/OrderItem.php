<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'subtotal',
        'cost_subtotal',
        'special_instructions',
        'status',
    ];

    protected $casts = [
        'unit_price'    => 'decimal:2',
        'unit_cost'     => 'decimal:2',
        'subtotal'      => 'decimal:2',
        'cost_subtotal' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function modifiers()
    {
        return $this->hasMany(OrderItemModifier::class);
    }

    public function calculateSubtotal()
    {
        $modifiersTotal = $this->exists ? $this->modifiers()->sum('price') : 0;
        $this->subtotal      = ($this->unit_price + $modifiersTotal) * $this->quantity;
        $this->cost_subtotal = ($this->unit_cost ?? 0) * $this->quantity;

        if ($this->exists) {
            $this->save();
        }

        return $this;
    }
}
