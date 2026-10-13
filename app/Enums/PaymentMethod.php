<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case CARD = 'card';
    case E_WALLET = 'e_wallet';
    case CHECK = 'check';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CARD => 'Credit/Debit Card',
            self::E_WALLET => 'E-Wallet',
            self::CHECK => 'Check',
        };
    }
}
