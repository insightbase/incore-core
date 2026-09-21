<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read EmailEntity $email
 * @property-read int $email_id
 * @property-read LanguageEntity $language
 * @property-read int $language_id
 * @property-read ?string $subject
 * @property-read ?string $text
 */
class EmailLanguageEntity extends ActiveRow
{
}
