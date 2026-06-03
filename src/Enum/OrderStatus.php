<?php

namespace App\Enum;

enum OrderStatus: string
{
    case Cart = 'cart';
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    /**
     * Statuts modifiables par un administrateur depuis le backoffice.
     *
     * @return string[]
     */
    public static function adminEditableValues(): array
    {
        return [
            self::Pending->value,
            self::Paid->value,
            self::Cancelled->value,
            self::Refunded->value,
        ];
    }
}
