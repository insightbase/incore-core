<?php

namespace App\Component\DropCore;

enum ConsolePageEnum: string
{
    case Credits = 'credits';
    case Buy = 'buy';
    case Orders = 'orders';
    case Usage = 'usage';

    public static function tryFromString(?string $page): ?self
    {
        if (null === $page) {
            return null;
        }

        return self::tryFrom($page);
    }
}
