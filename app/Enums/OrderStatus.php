<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PREPARING = 'preparing';
    case READY = 'ready';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PREPARING => 'Preparing',
            self::READY => 'Ready',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PREPARING => 'info',
            self::READY => 'success',
            self::COMPLETED => 'green',
            self::CANCELLED => 'danger',
        };
    }
}
