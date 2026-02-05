<?php

namespace App\UI\Accessory\Admin\StaticPage;

use App\Component\Translator\Translator;
use App\Model\Admin\Module;
use App\Model\Admin\StaticPage;
use App\UI\Accessory\Admin\MainMenu\MainMenuItem;
use App\UI\Accessory\Admin\MainMenu\MainMenuItemFactory;
use App\UI\Accessory\Admin\MainMenu\MainMenuModule;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;
use Nette\Security\User;
use Nette\Utils\Strings;

class StaticPageFactory implements MainMenuModule
{
    private ActiveRow $module;

    public function __construct(
        private readonly Module              $moduleModel,
        private readonly StaticPage          $staticPageModel,
        private readonly MainMenuItemFactory $mainMenuItemFactory,
        private readonly User                $userSecurity,
        private readonly Translator          $translator,
    )
    {
        $this->module = $this->moduleModel->getBySystemName('staticPage');
    }

    public function getModule(): ActiveRow
    {
        return $this->module;
    }

    public function getMainMenus(): array
    {
        $ret = [];
        foreach($this->staticPageModel->getTable() as $staticPage){
            $ret[] = $this->mainMenuItemFactory->create($this->module, 'edit', Strings::truncate($staticPage->name, 25))
                ->addParam('id', $staticPage->id)
            ;
        }
        if($this->userSecurity->isAllowed($this->module->system_name, 'new')){
            $ret[] = $this->mainMenuItemFactory->create($this->module, 'new', $this->translator->translate('menu_staticPageNew'));
        }
        return $ret;
    }

    public function isActive(MainMenuItem $mainMenuItem, Presenter $presenter): bool
    {
        if($presenter->isLinkCurrent($this->module->presenter . ':*')){
            if(array_key_exists('id', $mainMenuItem->getParams()) && $mainMenuItem->getParams()['id'] === $presenter->getParameter('id')){
                return true;
            }
            if($presenter->isLinkCurrent($this->module->presenter . ':new') && $mainMenuItem->getAction() === 'new'){
                return true;
            }
        }
        return false;
    }
}