<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $system_name
 * @property-read string $name
 * @property-read string $text
 * @property-read string $subject
 * @property-read ?string $modifier
 * @property-read ?string $template
 */
class EmailEntity extends ActiveRow
{
}
