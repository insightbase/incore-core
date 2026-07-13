<?php

namespace App\Component\DropCore;

readonly class ConsoleUrlBuilder
{
    public function __construct(
        private string $consoleUrl,
    ) {}

    public function build(string $identityToken, ConsolePageEnum $page, ?string $env): string
    {
        $params = [
            'identity=' . $identityToken,
            'page=' . $page->value,
        ];
        if (null !== $env && '' !== $env) {
            $params[] = 'env=' . $env;
        }

        return $this->consoleUrl . '#' . implode('&', $params);
    }
}
