<?php

namespace App\Component\DropCore;

enum DropCoreEnvEnum: string
{
    case Demo = 'demo';
    case Prod = 'prod';

    /**
     * @return array<string, string>
     */
    public static function getToSelect(): array
    {
        $items = [];
        foreach (self::cases() as $case) {
            $items[$case->value] = $case->value;
        }

        return $items;
    }
}
