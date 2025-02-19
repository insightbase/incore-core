<?php

namespace App\UI\Accessory\Admin\Submenu;

interface SubMenuItemFactory
{
    public function create(string $name, string $action): SubMenuItem;
}
