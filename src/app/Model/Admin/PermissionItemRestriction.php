<?php

namespace App\Model\Admin;

use App\Model\Entity\PermissionItemRestrictionEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection;

readonly class PermissionItemRestriction implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<PermissionItemRestrictionEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('permission_item_restriction');
    }

    /**
     * @return list<array{role: string, module: string}>
     */
    public function getAllForAcl(): array
    {
        $rows = [];
        foreach ($this->getTable()->select('role.system_name AS role_name, module.system_name AS module_name') as $row) {
            $rows[] = ['role' => (string) $row['role_name'], 'module' => (string) $row['module_name']];
        }

        return $rows;
    }

    public function exists(int $roleId, int $moduleId): bool
    {
        return $this->getTable()->where('role_id', $roleId)->where('module_id', $moduleId)->count('*') > 0;
    }

    public function ensure(int $roleId, int $moduleId): void
    {
        if (!$this->exists($roleId, $moduleId)) {
            $this->getTable()->insert(['role_id' => $roleId, 'module_id' => $moduleId]);
        }
    }

    public function deleteByRoleAndModule(int $roleId, int $moduleId): void
    {
        $this->getTable()->where('role_id', $roleId)->where('module_id', $moduleId)->delete();
    }
}
