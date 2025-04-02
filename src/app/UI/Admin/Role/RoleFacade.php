<?php

namespace App\UI\Admin\Role;

use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
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
        private LogFacade $logFacade,
    ) {}

    public function create(NewData $data): void
    {
        $role = $this->roleModel->insert((array) $data);
        $this->logFacade->create(LogActionEnum::Created, 'role', $role->id);
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
        $this->logFacade->create(LogActionEnum::Updated, 'role', $role->id);
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
        $this->logFacade->create(LogActionEnum::SetAuthorization, 'role', $role->id);
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
