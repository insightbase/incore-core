<?php

namespace App\UI\Accessory\MainMenu;

use App\Model\Entity\ModuleEntity;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;
use Nette\Security\User;

interface MainMenuModule
{
    /**
     * @return ModuleEntity
     */
    public function getModule():ActiveRow;
    /**
     * @return array<MainMenuItem[]>
     */
    public function getMainMenus():array;

    public function isActive(MainMenuItem $mainMenuItem, Presenter $presenter):bool;
}