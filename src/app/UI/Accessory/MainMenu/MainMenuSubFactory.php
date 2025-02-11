<?php

namespace App\UI\Accessory\MainMenu;

interface MainMenuSubFactory
{
    public function create(string $action,string $title,array $params):MainMenuSub;
}