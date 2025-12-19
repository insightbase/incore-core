<?php

namespace App\UI\Admin\Module;

use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Model\Admin\EnumerationItem;
use App\Model\Admin\Module;
use App\Model\Entity\ModuleEntity;
use App\UI\Admin\Module\Form\EditData;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;
use Nette\Security\User;

readonly class ModuleFacade
{
    public function __construct(
        private LogFacade $logFacade,
        private User $userSecurity,
        private LinkGenerator $linkGenerator,
        private Module $moduleModel,
        private EnumerationItem $enumerationItemModel,
    )
    {
    }

    /**
     * @param ModuleEntity $module
     * @param EditData $data
     * @return void
     */
    public function update(ActiveRow $module, EditData $data): void
    {
        $module->update((array) $data);
        $this->logFacade->create(LogActionEnum::Updated, 'module', $module->id);
    }

    /**
     * @param ModuleEntity $module
     * @return bool
     */
    public function isAllowed(ActiveRow $module):bool
    {
        if($module->enumeration_id === null) {
            return $this->userSecurity->isAllowed($module->system_name, $module->action);
        }else{
            return $this->userSecurity->isAllowed('enumeration', 'record');
        }
    }

    /**
     * @param ModuleEntity $module
     * @return string
     * @throws InvalidLinkException
     */
    public function getHref(ActiveRow $module):string
    {
        if($module->enumeration_id !== null){
            return $this->linkGenerator->link('Admin:Enumeration:record', ['id' => $module->enumeration_id]);
        }
        if($module->presenter === null){
            return '#';
        }
        return $this->linkGenerator->link('Admin:' . $module->presenter . ':' . $module->action);
    }

    /**
     * @param Presenter $presenter
     * @return ModuleEntity[]
     */
    public function getTree(Presenter $presenter):array
    {
        $module = null;
        if($presenter->isLinkCurrent('Enumeration:record')){
            $module = $this->moduleModel->getByEnumerationId($presenter->getParameter('id'));
        }
        if($presenter->isLinkCurrent('Enumeration:editItem')){
            $enumerationItem = $this->enumerationItemModel->get($presenter->getParameter('id'));
            if($enumerationItem !== null) {
                $module = $this->moduleModel->getByEnumerationId($enumerationItem->enumeration_id);
            }
        }

        if($module === null) {
            $module = $this->moduleModel->getByPresenter($presenter->getName(), $presenter->getAction());
        }
        if (null === $module) {
            return [];
        }

        $tree = [$module->id => $module];
        while (null !== $module->parent) {
            $module = $module->parent;
            $tree[$module->id] = $module;
        }

        return array_reverse($tree, true);
    }
}
