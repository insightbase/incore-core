<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $value
 * @property-read LanguageEntity $language
 * @property-read int $language_id
 * @property-read TranslateEntity $translate
 * @property-read int $translate_id
 */
class TranslateLanguageEntity extends ActiveRow
{
}
