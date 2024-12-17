<?php

namespace App\UI\Accessory\PresenterTrait;

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

    public function injectStandardTemplate(ParameterBag $parameterBag, SubmenuFactory $submenuFactory): void{
        $this->onRender[] = function () use ($parameterBag, $submenuFactory): void {
            $this->template->setTranslator($this->translator);
            $this->template->webpackVersion = md5(FileSystem::read($parameterBag->wwwDir . '/dist/version.txt'));
            $this->template->submenuFactory = $submenuFactory;
            $this->template->layoutFile = dirname(__FILE__) . '/../../@layout.latte';
        };
        $this->onStartup[] = function (): void {
            $storage = $this->getUser()->getStorage();
            if($storage instanceof SessionStorage) {
                $storage->setNamespace('admin');
            }
        };
    }
}