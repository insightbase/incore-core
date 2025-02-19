<?php

namespace App\UI\Accessory\Admin\MainMenu;

use App\Model\Entity\ModuleEntity;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;

interface MainMenuModule
{
    /**
     * @return ModuleEntity
     */
    public function getModule():ActiveRow;
    /**
     * @return MainMenuItem
     */
    public function getMainMenus():array;

    public function isActive(MainMenuItem $mainMenuItem, Presenter $presenter):bool;
}