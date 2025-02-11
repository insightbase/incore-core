<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read ModuleEntity $module
 * @property-read PrivilegeEntity $privilege
 */
class ModulePrivilegeEntity extends ActiveRow
{
}
