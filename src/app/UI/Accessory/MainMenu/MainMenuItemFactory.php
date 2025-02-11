<?php

namespace App\UI\Accessory\MainMenu;

use App\Model\Entity\ModuleEntity;
use Nette\Database\Table\ActiveRow;

interface MainMenuItemFactory
{
    /**
     * @param ModuleEntity $module
     * @param string $action
     * @param string $title
     * @return MainMenuItem
     */
    public function create(ActiveRow $module, string $action, string $title):MainMenuItem;
}