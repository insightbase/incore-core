<?php

namespace App\UI\Accessory\PresenterTrait;

use App\Component\Image\ImageFacade;
use App\Core\Authenticator;
use App\Model\Enum\RoleEnum;
use App\Model\Language;
use App\Model\Module;
use App\Model\Role;
use App\UI\Accessory\ParameterBag;
use App\UI\Accessory\Submenu\SubmenuFactory;
use App\UI\BaseTemplate;
use Nette\Application\Attributes\Persistent;
use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette\DI\Attributes\Inject;
use Nette\Security\Authorizator;
use Nette\Security\Permission;
use Nette\Utils\FileSystem;

/**
 * @property-read BaseTemplate $template
 */
trait StandardTemplateTrait
{
    #[Inject]
    public \App\Component\Translator\Translator $translator;
    #[Persistent]
    public string $lang;

    public function injectStandardTemplate(ParameterBag $parameterBag, SubmenuFactory $submenuFactory, ImageFacade $imageFacade, Module $moduleModel, Language $languageModel, Authenticator $authenticator): void{
        $this->onRender[] = function () use ($parameterBag, $submenuFactory, $imageFacade, $moduleModel, $languageModel): void {
            $this->template->setTranslator($this->translator);
            $this->template->webpackVersion = md5(FileSystem::read($parameterBag->wwwDir . '/dist/version.txt'));
            $this->template->submenuFactory = $submenuFactory;
            $this->template->layoutFile = dirname(__FILE__) . '/../../@layout.latte';
            $this->template->basicFormFile = dirname(__FILE__) . '/../Form/basic-form.latte';
            $this->template->basicModalFile = dirname(__FILE__) . '/../Modal/basic-modal.latte';
            $this->template->imageFacade = $imageFacade;
            $this->template->menuModules = $moduleModel->getToMenu();
            $this->template->moduleModel = $moduleModel;
            $this->template->languages = $languages = $languageModel->getToTranslate();
            $this->template->moduleTree = $moduleModel->getTree($this->getName());
            foreach($languages as $language){
                if($language->is_default){
                    $this->template->defaultLanguage = $language;
                    break;
                }
            }
        };
        $this->onStartup[] = function () use ($authenticator): void {
            $this->translator->setLang($this->lang);
            $storage = $this->getUser()->getStorage();
            if($storage instanceof SessionStorage) {
                $storage->setNamespace('admin');
            }
            $this->getUser()->setAuthenticator($authenticator);
        };
    }
}