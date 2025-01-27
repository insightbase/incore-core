<?php

namespace App\UI\Role;

use App\Model\Entity\ModuleEntity;
use App\Model\Entity\RoleEntity;
use App\Model\Permission;
use App\Model\Role;
use App\UI\Role\Exception\SystematicRoleException;
use App\UI\Role\Form\EditData;
use App\UI\Role\Form\NewData;
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
    public function setAuthorization(ActiveRow $role, ActiveRow $module, Form\AuthorizationSetData $data): void
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
