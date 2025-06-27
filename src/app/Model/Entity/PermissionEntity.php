<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read RoleEntity $role
 * @property-read int $role_id
 * @property-read ModuleEntity $module
 * @property-read int $module_id
 * @property-read PrivilegeEntity $privilege
 * @property-read int $privilege_id
 * @property-read bool $active
 */
class PermissionEntity extends ActiveRow
{
}
