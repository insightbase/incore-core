<?php

namespace App\UI\Accessory\Submenu;

class SubmenuFactory
{
    /**
     * @var array<SubMenuItem>
     */
    private array $subMenus = [];

    public function __construct(
        private readonly SubMenuItemFactory $subMenuItemFactory,
    )
    {
    }

    public function addMenu(string $name, string $url):SubMenuItem
    {
        $subMenuItem = $this->subMenuItemFactory->create($name, $url);
        $this->subMenus[] = $subMenuItem;
        return $subMenuItem;
    }

    public function getSubMenus(): array
    {
        return $this->subMenus;
    }
}