<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read LanguageEntity $language
 * @property-read int $language_id
 * @property-read LanguageEntity $locale
 * @property-read int $locale_id
 * @property-read string $name
 */
class LanguageLocaleEntity extends ActiveRow
{
}
