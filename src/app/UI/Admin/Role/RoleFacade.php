<?php

namespace App\UI\Admin\Role;

use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Core\Admin\ItemPermission\ItemPermissionProviderRegistry;
use App\Model\Admin\Permission;
use App\Model\Admin\PermissionItem;
use App\Model\Admin\PermissionItemRestriction;
use App\Model\Admin\Role;
use App\Model\Entity\ModuleEntity;
use App\Model\Entity\RoleEntity;
use App\UI\Admin\Role\Exception\SystematicRoleException;
use App\UI\Admin\Role\Form\AuthorizationSetData;
use App\UI\Admin\Role\Form\EditData;
use App\UI\Admin\Role\Form\NewData;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

readonly class RoleFacade
{
    public function __construct(
        private Role $roleModel,
        private Permission $permissionModel,
        private LogFacade $logFacade,
        private Explorer $explorer,
        private ItemPermissionProviderRegistry $itemPermissionProviderRegistry,
        private PermissionItemRestriction $permissionItemRestrictionModel,
        private PermissionItem $permissionItemModel,
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
    public function setAuthorization(ActiveRow $role, ActiveRow $module, AuthorizationSetData $data): void
    {
        $this->explorer->transaction(function () use ($role, $module, $data): void {
            foreach ($data->privileges as $privilegeId) {
                $permission = $this->permissionModel->getByRoleAndModuleAndPrivilegeId($role, $module, $privilegeId);
                if (null === $permission) {
                    $this->permissionModel->insert([
                        'role_id' => $role->id,
                        'module_id' => $module->id,
                        'privilege_id' => $privilegeId,
                    ]);
                }elseif($permission->active === 0){
                    $permission->update(['active' => true]);
                }
            }
            $this->permissionModel->getByRoleAndModuleAndNotPrivilegesId($role, $module, $data->privileges)->update(['active' => false]);

            if ($this->itemPermissionProviderRegistry->has($module->system_name)) {
                $this->setItemRestriction($role, $module, $data);
            }
        });
        $this->logFacade->create(LogActionEnum::SetAuthorization, 'role', $role->id);
    }

    /**
     * @param RoleEntity   $role
     * @param ModuleEntity $module
     */
    private function setItemRestriction(ActiveRow $role, ActiveRow $module, AuthorizationSetData $data): void
    {
        if (!$data->itemRestricted) {
            $this->permissionItemRestrictionModel->deleteByRoleAndModule($role->id, $module->id);
            $this->permissionItemModel->deleteByRoleAndModule($role->id, $module->id);

            return;
        }

        $this->permissionItemRestrictionModel->ensure($role->id, $module->id);
        $wanted = array_map('intval', $data->items);
        $current = $this->permissionItemModel->getItemIds($role->id, $module->id);
        foreach (array_diff($wanted, $current) as $itemId) {
            $this->permissionItemModel->insertIfMissing($role->id, $module->id, $itemId);
        }
        // smaže odškrtnuté i osiřelé položky, které formulář nezobrazil
        $removed = array_values(array_diff($current, $wanted));
        if ($removed !== []) {
            $this->permissionItemModel->deleteByRoleAndModule($role->id, $module->id, $removed);
        }
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

    /**
     * @param RoleEntity $role
     * @return void
     */
    public function delete(ActiveRow $role):void
    {
        $role->delete();
    }
}
