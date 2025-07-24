<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $locale
 * @property-read ?string $host
 * @property-read ?ImageEntity $flag
 * @property-read ?int $flag_id
 * @property-read bool $is_default
 * @property-read string $url
 * @property-read bool $active
 * @property-read ?string $drop_core_id
 * @property-read ?DateTime $drop_core_last_call_date
 */
class LanguageEntity extends ActiveRow
{
}
