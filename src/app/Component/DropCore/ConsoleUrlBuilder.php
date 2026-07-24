<?php

namespace App\Component\DropCore;

readonly class ConsoleUrlBuilder
{
    public function __construct(
        private string $consoleUrl,
    ) {}

    public function build(string $identityToken, ConsolePageEnum $page, ?string $env, ?string $callback = null): string
    {
        $params = [
            'identity='.rawurlencode($identityToken),
            'page='.$page->value,
        ];
        if (null !== $env && '' !== $env) {
            $params[] = 'env='.rawurlencode($env);
        }
        if (null !== $callback && '' !== $callback) {
            $params[] = 'callback='.rawurlencode($callback);
        }

        return $this->consoleUrl.'#'.implode('&', $params);
    }
}
