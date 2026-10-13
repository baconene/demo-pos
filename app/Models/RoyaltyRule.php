<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Legacy royalty-rule administration used by the existing profit-sharing routes. */
class RoyaltyRule extends Model
{
    protected $fillable = [
        'scope', 'product_id', 'category_id', 'recipient_name', 'shareholder_id',
        'royalty_percentage', 'effective_date', 'expiration_date', 'is_active',
    ];

    protected $casts = [
        'royalty_percentage' => 'decimal:2',
        'effective_date' => 'date',
        'expiration_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function shareholder()
    {
        return $this->belongsTo(Shareholder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
