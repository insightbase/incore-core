<?php

namespace App\Model\Admin;

use App\Model\Entity\ModuleEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Security\User;

readonly class Module implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<ModuleEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('module');
    }

    /**
     * @param ?ModuleEntity $parent
     *
     * @return Selection<ModuleEntity>
     */
    public function getToMenu(?ActiveRow $parent = null): Selection
    {
        return $this->getTable()
            ->where('parent_id', $parent?->id)
            ->where('active', true)
            ->order('position')
            ;
    }

    /**
     * @return ?ModuleEntity
     */
    public function getBySystemName(string $systemName): ?ActiveRow
    {
        return $this->getTable()->where('system_name', $systemName)->fetch();
    }

    /**
     * @return ?ModuleEntity
     */
    public function getByPresenter(string $presenterName, string $action = 'default'): ?ActiveRow
    {
        $presenterName = str_replace('Admin:', '', $presenterName);

        $module =  $this->getTable()
            ->where('presenter', $presenterName)
            ->where('action', $action)
            ->fetch();
        if($module === null && $action !== 'default') {
            $module =  $this->getTable()
                ->where('presenter', $presenterName)
                ->where('action', 'default')
                ->fetch();
        };
        return $module;
    }

    /**
     * @return Selection<ModuleEntity>
     */
    public function getToGrid(): Selection
    {
        return $this->getTable();
    }

    /**
     * @return ?ModuleEntity
     */
    public function get(int $id): ?ActiveRow
    {
        return $this->getTable()->get($id);
    }

    /**
     * @return Selection<ModuleEntity>
     */
    public function getNotParent(): Selection
    {
        return $this->getTable()->where('parent_id', null);
    }

    /**
     * @return Selection<ModuleEntity>
     */
    public function getToGridAuthorizationSet(User $user): Selection
    {
        $moduleIds = [];
        foreach ($this->getToGrid() as $module) {
            if ($user->isAllowed($module->system_name, 'default')) {
                $moduleIds[] = $module->id;
            }
        }

        return $this->getTable()->where('id', $moduleIds);
    }

    /**
     * @param int $id
     * @return ?ModuleEntity
     */
    public function getByEnumerationId(int $id):?ActiveRow
    {
        return $this->getTable()
            ->where('enumeration_id', $id)
            ->fetch();
    }
}
