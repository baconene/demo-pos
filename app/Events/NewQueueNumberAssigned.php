<?php

namespace App\Events;

use App\Models\QueueNumber;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewQueueNumberAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(public QueueNumber $queueNumber) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('queue'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'queue.new-number';
    }
}
