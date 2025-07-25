<?php

namespace App\Model\Admin;

use App\Model\Entity\ModuleEntity;
use App\Model\Entity\PermissionEntity;
use App\Model\Entity\RoleEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Permission implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<PermissionEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('permission');
    }

    /**
     * @return Selection<PermissionEntity>
     */
    public function getToAuthorizator():Selection
    {
        return $this->getTable()->where('active', true);
    }

    /**
     * @param RoleEntity   $role
     * @param ModuleEntity $module
     *
     * @return Selection<PermissionEntity>
     */
    public function getByRoleAndModule(ActiveRow $role, ActiveRow $module): Selection
    {
        return $this->getTable()->where('role_id', $role->id)
            ->where('module_id', $module->id)
            ->where('active', true)
        ;
    }

    /**
     * @param RoleEntity   $role
     * @param ModuleEntity $module
     *
     * @return ?PermissionEntity
     */
    public function getByRoleAndModuleAndPrivilegeId(ActiveRow $role, ActiveRow $module, int $privilegeId): ?ActiveRow
    {
        return $this->getTable()->where('role_id', $role->id)
            ->where('module_id', $module->id)
            ->where('privilege_id', $privilegeId)
            ->fetch()
        ;
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    public function insert(array $data): void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @param RoleEntity   $role
     * @param ModuleEntity $module
     *
     * @return Selection<PermissionEntity>
     */
    public function getByRoleAndModuleAndNotPrivilegesId(ActiveRow $role, ActiveRow $module, array $privilegeIds): Selection
    {
        return $this->getTable()
            ->where('role_id', $role->id)
            ->where('module_id', $module->id)
            ->where('NOT privilege_id', $privilegeIds)
        ;
    }
}
