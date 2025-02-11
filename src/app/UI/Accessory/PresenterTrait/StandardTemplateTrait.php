<?php

namespace App\UI\Accessory\PresenterTrait;

use App\Component\Image\ImageControlFactory;
use App\Component\Image\ImageFacade;
use App\Component\Translator\Translator;
use App\Core\Authenticator;
use App\Model\Language;
use App\Model\Module;
use App\Model\Setting;
use App\UI\Accessory\MainMenu\MainMenuFactory;
use App\UI\Accessory\ParameterBag;
use App\UI\Accessory\Submenu\SubmenuFactory;
use App\UI\BaseTemplate;
use Nette\Application\Attributes\Persistent;
use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette\DI\Attributes\Inject;
use Nette\Utils\FileSystem;

/**
 * @property BaseTemplate $template
 */
trait StandardTemplateTrait
{
    #[Inject]
    public Translator $translator;
    #[Persistent]
    public string $lang;
    #[Inject]
    public MainMenuFactory $mainMenuFactory;
    #[Inject]
    public ImageControlFactory $imageControlFactory;

    protected function createComponentImage():ImageControlFactory
    {
        return $this->imageControlFactory;
    }

    public function injectStandardTemplate(ParameterBag $parameterBag, SubmenuFactory $submenuFactory, ImageFacade $imageFacade,
                                           Module $moduleModel, Language $languageModel, Authenticator $authenticator, Setting $settingModel
    ): void
    {
        $this->onRender[] = function () use ($parameterBag, $submenuFactory, $imageFacade, $moduleModel, $languageModel, $settingModel): void {
            $this->template->setTranslator($this->translator);
            $this->template->webpackVersion = md5(FileSystem::read($parameterBag->wwwDir.'/dist/version.txt'));
            $this->template->submenuFactory = $submenuFactory;
            $this->template->layoutFile = dirname(__FILE__).'/../../@layout.latte';
            $this->template->basicFormFile = dirname(__FILE__).'/../Form/basic-form.latte';
            $this->template->basicModalFile = dirname(__FILE__).'/../Modal/basic-modal.latte';
            $this->template->imageFacade = $imageFacade;
            $this->template->menuModules = $moduleModel->getToMenu();
            $this->template->moduleModel = $moduleModel;
            $this->template->languages = $languages = $languageModel->getToTranslate();
            $this->template->moduleTree = $moduleModel->getTree($this->getName());
            $this->template->mainMenuFactory = $this->mainMenuFactory;
            $this->template->setting = $settingModel->getDefault();
            foreach ($languages as $language) {
                if ($language->is_default) {
                    $this->template->defaultLanguage = $language;

                    break;
                }
            }
        };
        $this->onStartup[] = function () use ($authenticator): void {
            $this->translator->setLang($this->lang);
            $storage = $this->getUser()->getStorage();
            if ($storage instanceof SessionStorage) {
                $storage->setNamespace('admin');
            }
            $this->getUser()->setAuthenticator($authenticator);
        };
    }
}
