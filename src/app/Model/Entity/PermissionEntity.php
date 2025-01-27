<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property int             $id
 * @property RoleEntity      $role
 * @property ModuleEntity    $module
 * @property PrivilegeEntity $privilege
 */
class PermissionEntity extends ActiveRow {}
