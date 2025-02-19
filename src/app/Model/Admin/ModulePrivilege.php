<?php

namespace App\Model\Admin;

use App\Model\Entity\ModuleEntity;
use App\Model\Entity\ModulePrivilegeEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class ModulePrivilege implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<ModulePrivilegeEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('module_privilege');
    }

    /**
     * @param ModuleEntity $module
     *
     * @return Selection<ModulePrivilegeEntity>
     */
    public function getByModule(ActiveRow $module): Selection
    {
        return $this->getTable()->where('module_id', $module->id);
    }
}
