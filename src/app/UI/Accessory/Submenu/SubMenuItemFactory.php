<?php

namespace App\UI\Accessory\Submenu;

interface SubMenuItemFactory
{
    public function create(string $name, string $action): SubMenuItem;
}
