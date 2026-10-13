<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parcel extends Model
{
    protected $fillable = ['parcel_number', 'name', 'assigned_personnel', 'status', 'notes'];

    public function items()
    {
        return $this->hasMany(ParcelItem::class);
    }

    /** Recompute and persist parcel status based on item statuses. */
    public function syncStatus(): void
    {
        $items = $this->items()->get();

        if ($items->isEmpty()) {
            $status = 'in';
        } elseif ($items->every(fn ($i) => $i->status === 'out')) {
            $status = 'complete';
        } elseif ($items->every(fn ($i) => $i->status === 'in')) {
            $status = 'in';
        } else {
            $status = 'out'; // mixed — closing in progress
        }

        $this->update(['status' => $status]);
    }

    /** Auto-generate next sequential parcel number: P-001, P-002 … */
    public static function nextNumber(): string
    {
        $last = static::orderByDesc('id')->value('parcel_number');
        if (!$last) return 'P-001';
        $n = (int) preg_replace('/\D/', '', $last);
        return 'P-' . str_pad($n + 1, 3, '0', STR_PAD_LEFT);
    }
}
