<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $presenter
 * @property-read string $input
 * @property-read ?string $label_help
 */
class FormHelpEntity extends ActiveRow
{
}
