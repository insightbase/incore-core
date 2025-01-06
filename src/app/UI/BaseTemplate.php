<?php

namespace App\UI;

use App\Component\Image\ImageFacade;
use App\Model\Entity\ModuleEntity;
use App\UI\Accessory\Submenu\SubmenuFactory;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Database\Table\Selection;

class BaseTemplate extends Template
{
    public Presenter $presenter;
    public string $webpackVersion;
    public string $layoutFile;
    public SubmenuFactory $submenuFactory;
    public array $flashes;
    public string $basicFormFile;
    public string $basicModalFile;
    public ImageFacade $imageFacade;
    /**
     * @var Selection<ModuleEntity>
     */
    public Selection $menuModules;
}