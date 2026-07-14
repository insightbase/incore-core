<?php

declare(strict_types=1);

namespace App\UI\Accessory\Admin\Form;

use Doctrine\ORM\Mapping\Column;
use ReflectionClass;

final class EntityColumnLengthReader
{
    private const array STRING_TYPES = ['string', 'ascii_string'];
    private const int DEFAULT_LENGTH = 255;

    /** @var array<class-string, array<string, int>> */
    private static array $cache = [];

    /**
     * @param class-string $entityClass
     * @return array<string, int>
     */
    public static function getLengths(string $entityClass): array
    {
        if (isset(self::$cache[$entityClass])) {
            return self::$cache[$entityClass];
        }

        $lengths = [];
        $reflection = new ReflectionClass($entityClass);

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);
            if ($attributes === []) {
                continue;
            }

            $column = $attributes[0]->newInstance();
            $type = $column->type ?? 'string';
            if (!in_array($type, self::STRING_TYPES, true)) {
                continue;
            }

            $lengths[$property->getName()] = $column->length ?? self::DEFAULT_LENGTH;
        }

        return self::$cache[$entityClass] = $lengths;
    }
}
