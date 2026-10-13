<?php

namespace App\Events;

use App\Models\Ingredient;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(public Ingredient $ingredient) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('inventory'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'inventory.low-stock';
    }
}
