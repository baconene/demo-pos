<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewReceiptEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $receiptPayload) {}

    public function broadcastOn(): array
    {
        // Android subscribes to 'orders' channel
        return [new Channel('orders')];
    }

    public function broadcastAs(): string
    {
        // Android binds to both "App\Events\NewReceiptEvent" AND "print".
        // Using "print" avoids any backslash encoding issues across Pusher clients.
        return 'print';
    }

    public function broadcastWith(): array
    {
        // Android's handleIncomingPrint checks for 'receipt' key in the event data.
        return ['receipt' => $this->receiptPayload];
    }
}
