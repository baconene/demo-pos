<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemModifierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->modifier->id,
            'name' => $this->modifier->name,
            'price' => $this->price,
        ];
    }
}
