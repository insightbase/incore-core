<?php

namespace App\UI\Admin\Role;

use App\Model\Admin\Permission;
use App\Model\Admin\Role;
use App\Model\Entity\ModuleEntity;
use App\Model\Entity\RoleEntity;
use App\UI\Admin\Role\Exception\SystematicRoleException;
use App\UI\Admin\Role\Form\EditData;
use App\UI\Admin\Role\Form\NewData;
use Nette\Database\Table\ActiveRow;

readonly class RoleFacade
{
    public function __construct(
        private Role $roleModel,
        private Permission $permissionModel,
    ) {}

    public function create(NewData $data): void
    {
        $this->roleModel->insert((array) $data);
    }

    /**
     * @param RoleEntity $role
     *
     * @throws SystematicRoleException
     */
    public function update(ActiveRow $role, EditData $data): void
    {
        $this->check($role);
        $role->update((array) $data);
    }

    /**
     * @param RoleEntity   $role
     * @param ModuleEntity $module
     */
    public function setAuthorization(ActiveRow $role, ActiveRow $module, \App\UI\Admin\Role\Form\AuthorizationSetData $data): void
    {
        foreach ($data->privileges as $privilegeId) {
            $permission = $this->permissionModel->getByRoleAndModuleAndPrivilegeId($role, $module, $privilegeId);
            if (null === $permission) {
                $this->permissionModel->insert([
                    'role_id' => $role->id,
                    'module_id' => $module->id,
                    'privilege_id' => $privilegeId,
                ]);
            }
        }
        $this->permissionModel->getByRoleAndModuleAndNotPrivilegesId($role, $module, $data->privileges)->delete();
    }

    /**
     * @param RoleEntity $role
     *
     * @throws SystematicRoleException
     */
    private function check(ActiveRow $role): void
    {
        if ($role->is_systemic) {
            throw new SystematicRoleException();
        }
    }
}
