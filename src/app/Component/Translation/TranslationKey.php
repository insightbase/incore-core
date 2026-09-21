<?php

namespace App\Component\Translation;

/**
 * Klíč jedné přeložitelné hodnoty v dávce pro DropCore.
 *
 * Tvar `systemName:id:pole`. Dvojtečka se nemůže vyskytnout ve jméně zdroje
 * (validace) ani v názvu pole, takže rozklad je jednoznačný — na rozdíl
 * od původních klíčů oddělených podtržítkem.
 */
final readonly class TranslationKey
{
    private const string SEPARATOR = ':';
    private const string SYSTEM_NAME_PATTERN = '~^[a-z][a-zA-Z0-9]*$~';

    public function __construct(
        public string $systemName,
        public int $id,
        public string $field,
    ) {}

    public static function encode(string $systemName, int $id, string $field): string
    {
        return $systemName . self::SEPARATOR . $id . self::SEPARATOR . $field;
    }

    public static function tryDecode(string $key): ?self
    {
        $parts = explode(self::SEPARATOR, $key);
        if (count($parts) !== 3) {
            return null;
        }

        [$systemName, $id, $field] = $parts;
        if (!self::isValidSystemName($systemName) || $field === '' || !ctype_digit($id)) {
            return null;
        }

        return new self($systemName, (int) $id, $field);
    }

    public static function isValidSystemName(string $systemName): bool
    {
        return 1 === preg_match(self::SYSTEM_NAME_PATTERN, $systemName);
    }
}
