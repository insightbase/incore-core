<?php

namespace App\Core;

final class DbParameterBag
{
    public function __construct(
        public string $host,
        public string $dbname,
        public string $user,
        public string $password,
    ) {}
}
