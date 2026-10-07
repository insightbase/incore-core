<?php

namespace App\Core\Admin;

use App\Core\Admin\ItemPermission\ItemRestrictionMap;
use App\Model\Admin\Module;
use App\Model\Admin\PermissionItem;
use App\Model\Admin\PermissionItemRestriction;
use App\Model\Admin\Role;
use App\Model\Enum\RoleEnum;
use Nette\Security\Permission;

readonly class AuthorizatorFactory
{
    public function __construct(
        private Role                        $roleModel,
        private Module                      $moduleModel,
        private \App\Model\Admin\Permission $permissionModel,
        private PermissionItemRestriction   $permissionItemRestrictionModel,
        private PermissionItem              $permissionItemModel,
    ) {}

    public function create(): Permission
    {
        $rules = [];
        foreach ($this->permissionModel->getToAuthorizator() as $permission) {
            $rules[] = [
                'role' => $permission->role->system_name,
                'module' => $permission->module->system_name,
                'privilege' => $permission->privilege->system_name,
            ];
        }

        return AclBuilder::build(
            array_values($this->roleModel->getTable()->fetchPairs(null, 'system_name')),
            array_values($this->moduleModel->getTable()->fetchPairs(null, 'system_name')),
            $rules,
            $this->roleModel->getBySystemName(RoleEnum::SUPER_ADMIN->value)->system_name,
            ItemRestrictionMap::fromRows(
                $this->permissionItemRestrictionModel->getAllForAcl(),
                $this->permissionItemModel->getAllForAcl(),
            ),
        );
    }
}
