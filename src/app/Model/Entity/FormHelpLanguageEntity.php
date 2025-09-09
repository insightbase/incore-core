<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read LanguageEntity $language
 * @property-read int $language_id
 * @property-read FormHelpEntity $form_help
 * @property-read int $form_help_id
 * @property-read ?string $label_help
 */
class FormHelpLanguageEntity extends ActiveRow
{
}
