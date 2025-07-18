<?php

namespace App\Core\Admin;

use App\Model\Admin\Module;
use App\Model\Admin\Role;
use App\Model\Enum\RoleEnum;
use Nette\Security\Permission;

readonly class AuthorizatorFactory
{
    public function __construct(
        private Role                        $roleModel,
        private Module                      $moduleModel,
        private \App\Model\Admin\Permission $permissionModel,
    ) {}

    public function create(): Permission
    {
        $acl = new Permission();
        foreach ($this->roleModel->getTable() as $role) {
            $acl->addRole($role->system_name);
        }
        foreach ($this->moduleModel->getTable() as $module) {
            $acl->addResource($module->system_name);
        }

        foreach ($this->permissionModel->getToAuthorizator() as $permission) {
            $acl->allow($permission->role->system_name, $permission->module->system_name, $permission->privilege->system_name);
        }

        $roleSuperAdmin = $this->roleModel->getBySystemName(RoleEnum::SUPER_ADMIN->value);
        $acl->allow($roleSuperAdmin->system_name);

        return $acl;
    }
}
