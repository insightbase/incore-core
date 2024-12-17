<?php

namespace App\UI;

use App\UI\Accessory\Submenu\SubmenuFactory;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;

class BaseTemplate extends Template
{
    public Presenter $presenter;
    public string $webpackVersion;
    public string $layoutFile;
    public SubmenuFactory $submenuFactory;
    public array $flashes;
}