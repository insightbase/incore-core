<?php

namespace App\Model;

use App\Model\Entity\ModuleEntity;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Module implements Model
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @return Selection<ModuleEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('module');
    }

    /**
     * @param ?ModuleEntity $parent
     * @return Selection<ModuleEntity>
     */
    public function getToMenu(?ActiveRow $parent = null):Selection
    {
        return $this->getTable()->where('parent_id', $parent?->id);
    }

    /**
     * @param string $systemName
     * @return ?ModuleEntity
     */
    public function getBySystemName(string $systemName): ?ActiveRow
    {
        return $this->getTable()->where('system_name', $systemName)->fetch();
    }

    /**
     * @param string $presenterName
     * @return ?ModuleEntity
     */
    public function getByPresenter(string $presenterName): ?ActiveRow
    {
        return $this->getTable()->where('presenter', $presenterName)->fetch();
    }

    /**
     * @param string $presenterName
     * @return ModuleEntity[]
     */
    public function getTree(string $presenterName): array
    {
        $module = $this->getByPresenter($presenterName);
        if($module === null){
            return [];
        }

        $tree = [$module->id => $module];
        while($module->parent !== null){
            $module = $module->parent;
            $tree[$module->id] = $module;
        }
        return array_reverse($tree, true);
    }

    /**
     * @return Selection<ModuleEntity>
     */
    public function getToGrid():Selection
    {
        return $this->getTable();
    }

    /**
     * @param int $id
     * @return ?ModuleEntity
     */
    public function get(int $id):?ActiveRow
    {
        return $this->getTable()->get($id);
    }

    /**
     * @return Selection<ModuleEntity>
     */
    public function getNotParent():Selection
    {
        return $this->getTable()->where('parent_id', null);
    }

    /**
     * @return Selection<ModuleEntity>
     */
    public function getToGridAuthorizationSet(\Nette\Security\User $user):Selection
    {
        $moduleIds = [];
        foreach($this->getToGrid() as $module){
            if($user->isAllowed($module->system_name, 'default')){
                $moduleIds[] = $module->id;
            }
        }

        return $this->getTable()->where('id', $moduleIds);
    }
}