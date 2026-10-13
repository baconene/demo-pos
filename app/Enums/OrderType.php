<?php

namespace App\Enums;

enum OrderType: string
{
    case DINE_IN = 'dine_in';
    case TAKEOUT = 'takeout';
    case DELIVERY = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::DINE_IN => 'Dine In',
            self::TAKEOUT => 'Takeout',
            self::DELIVERY => 'Delivery',
        };
    }
}
