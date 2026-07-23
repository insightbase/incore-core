<?php

namespace App\Component\DropCore;

readonly class DropCoreConfig
{
    public function __construct(
        public string $apiUrl,
        public string $store,
        public string $identityToken,
    ) {}
}
