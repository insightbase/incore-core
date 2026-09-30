<?php

namespace App\Component\DropCore;

enum DropCoreEnvEnum: string
{
    case Demo = 'demo';
    case Prod = 'prod';
    /** Jen pro vývoj (debug režim): překlad se neposílá do DropCore, simuluje ho DropCoreSimulator. */
    case Simulation = 'simulation';

    /**
     * @param bool $withSimulation nabídnout i simulaci (jen v debug režimu)
     * @return array<string, string>
     */
    public static function getToSelect(bool $withSimulation = false): array
    {
        $items = [];
        foreach (self::cases() as $case) {
            if ($case === self::Simulation && !$withSimulation) {
                continue;
            }
            $items[$case->value] = $case->value;
        }

        return $items;
    }
}
