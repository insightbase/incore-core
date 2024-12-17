<?php

namespace App\UI\Accessory;

class ParameterBag
{
    public function __construct(
        public string $wwwDir,
        public bool $debugMode,
    )
    {
    }
}