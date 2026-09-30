<?php

namespace App\Component\DropCore;

readonly class DropCoreConfig
{
    public function __construct(
        public string $apiUrl,
        public string $store,
        public string $identityToken,
        /** Překlad jen simulovat (DropCoreSimulator), nic neposílat do DropCore. */
        public bool $simulation = false,
    ) {}
}
