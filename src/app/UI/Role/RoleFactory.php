<?php

namespace App\UI\Role;

use App\Model\Entity\RoleEntity;
use App\Model\Role;
use App\UI\Role\Exception\SystematicRoleException;
use App\UI\Role\Form\EditData;
use App\UI\Role\Form\NewData;
use Nette\Database\Table\ActiveRow;

readonly class RoleFactory
{
    public function __construct(
        private Role $roleModel,
    )
    {
    }

    /**
     * @param RoleEntity $role
     * @return void
     * @throws SystematicRoleException
     */
    private function check(ActiveRow $role):void{
        if($role->is_systemic){
            throw new SystematicRoleException();
        }
    }

    public function create(NewData $data):void
    {
        $this->roleModel->insert((array)$data);
    }

    /**
     * @param RoleEntity $role
     * @param EditData $data
     * @return void
     * @throws SystematicRoleException
     */
    public function update(ActiveRow $role, EditData $data):void
    {
        $this->check($role);
        $role->update((array)$data);
    }
}