<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'item_type' => $this->item_type ?? 'ingredient',
            'unit' => $this->unit,
            'current_quantity' => $this->current_quantity,
            'min_quantity' => $this->min_quantity,
            'cost_per_unit' => (float) $this->cost_per_unit,
            'is_low_stock' => $this->isLowStock(),
            'track_inventory' => $this->track_inventory,
            'is_active' => $this->is_active,
        ];
    }
}
