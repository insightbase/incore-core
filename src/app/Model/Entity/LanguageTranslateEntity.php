<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $drop_core_id
 * @property-read UserEntity $user
 * @property-read int $user_id
 * @property-read LanguageEntity $language
 * @property-read int $language_id
 * @property-read DateTime $datetime
 * @property-read ?DateTime $finished
 * @property-read string $request
 */
class LanguageTranslateEntity extends ActiveRow
{
}
