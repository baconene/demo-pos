<?php

namespace App\Enums;

enum QueueStatus: string
{
    case WAITING = 'waiting';
    case PREPARING = 'preparing';
    case READY = 'ready';
    case SERVED = 'served';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::WAITING => 'Waiting',
            self::PREPARING => 'Preparing',
            self::READY => 'Ready for Pickup',
            self::SERVED => 'Served',
            self::CANCELLED => 'Cancelled',
        };
    }
}
