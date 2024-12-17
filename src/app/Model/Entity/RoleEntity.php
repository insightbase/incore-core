<?php

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $system_name
 */
class RoleEntity extends ActiveRow
{
}