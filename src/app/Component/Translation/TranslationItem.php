<?php

namespace App\Component\Translation;

/**
 * Jedna přeložitelná hodnota. `$value` je pole u obsahu ve formátu EditorJs
 * nebo jiného JSON, jinak řetězec.
 */
final readonly class TranslationItem
{
    public function __construct(
        public int $id,
        public string $field,
        public string|array $value,
    ) {}
}
