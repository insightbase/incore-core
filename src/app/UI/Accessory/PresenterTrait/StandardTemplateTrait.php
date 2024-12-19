<?php

namespace App\UI\Accessory\PresenterTrait;

use App\Component\Image\ImageFacade;
use App\Model\Module;
use App\UI\Accessory\ParameterBag;
use App\UI\Accessory\Submenu\SubmenuFactory;
use App\UI\BaseTemplate;
use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette\DI\Attributes\Inject;
use Nette\Utils\FileSystem;

/**
 * @property-read BaseTemplate $template
 */
trait StandardTemplateTrait
{
    #[Inject]
    public \App\Component\Translator\Translator $translator;

    public function injectStandardTemplate(ParameterBag $parameterBag, SubmenuFactory $submenuFactory, ImageFacade $imageFacade, Module $moduleModel): void{
        $this->onRender[] = function () use ($parameterBag, $submenuFactory, $imageFacade, $moduleModel): void {
            $this->template->setTranslator($this->translator);
            $this->template->webpackVersion = md5(FileSystem::read($parameterBag->wwwDir . '/dist/version.txt'));
            $this->template->submenuFactory = $submenuFactory;
            $this->template->layoutFile = dirname(__FILE__) . '/../../@layout.latte';
            $this->template->basicFormFile = dirname(__FILE__) . '/../Form/basic-form.latte';
            $this->template->imageFacade = $imageFacade;
            $this->template->menuModules = $moduleModel->getToMenu();
        };
        $this->onStartup[] = function (): void {
            $storage = $this->getUser()->getStorage();
            if($storage instanceof SessionStorage) {
                $storage->setNamespace('admin');
            }
        };
    }
}