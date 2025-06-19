<?php

namespace App\UI\Accessory\Admin\Translate;

use App\Component\Translator\Translator;
use App\Model\Admin\Module;
use App\Model\Admin\Translate;
use App\UI\Accessory\Admin\MainMenu\MainMenuItem;
use App\UI\Accessory\Admin\MainMenu\MainMenuItemFactory;
use App\UI\Accessory\Admin\MainMenu\MainMenuModule;
use App\UI\Admin\Translate\TranslatePresenter;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Arrays;

class TranslateMainMenuFactory implements MainMenuModule
{
    private ActiveRow $module;

    public function __construct(
        private readonly Module              $moduleModel,
        private readonly Translate           $translateModel,
        private readonly MainMenuItemFactory $mainMenuItemFactory,
        private readonly Translator          $translator,
    )
    {
        $this->module = $this->moduleModel->getBySystemName('translates');
    }

    public function getModule(): ActiveRow
    {
        return $this->module;
    }

    public function getMainMenus(): array
    {
        $ret = [];
        foreach($this->translateModel->getFirstKey('front') as $key){
            $ret[] = $menu = $this->mainMenuItemFactory->create($this->module, 'default', $this->translator->translate($key['prefix']))
                ->addParam('source', 'front')
                ->addParam('key', $key['prefix']);
            foreach($this->translateModel->getSecondKey('front', $key['prefix']) as $key2){
                $menu->addSubMenu($this->mainMenuItemFactory->create($this->module, 'default', $this->translator->translate($key2['prefix']))
                    ->addParam('source', 'front')
                    ->addParam('key', $key2['prefix']))
                ;
            }
        }
        $ret[] = $this->mainMenuItemFactory->create($this->module, 'default', $this->translator->translate('menu_front'))
            ->addParam('source', 'front')
            ->addParam('key', '');
        return $ret;
    }

    /**
     * @param MainMenuItem $mainMenuItem
     * @param TranslatePresenter $presenter
     * @return bool
     */
    public function isActive(MainMenuItem $mainMenuItem, Presenter $presenter): bool
    {
        $keys = explode('.', $presenter->key);
        $lastKey = Arrays::last(explode('.', $mainMenuItem->getParams()['key']));
        return in_array($lastKey, $keys);
    }
}