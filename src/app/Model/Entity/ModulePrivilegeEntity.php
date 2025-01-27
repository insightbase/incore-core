<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property int             $id
 * @property ModuleEntity    $module
 * @property PrivilegeEntity $privilege
 */
class ModulePrivilegeEntity extends ActiveRow {}
