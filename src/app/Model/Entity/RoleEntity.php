<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $system_name
 * @property-read bool $is_systemic
 */
class RoleEntity extends ActiveRow
{
}
