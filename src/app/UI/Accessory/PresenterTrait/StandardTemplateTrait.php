<?php

namespace App\UI\Accessory\PresenterTrait;

use App\Component\Image\Exception\ImageNotFoundException;
use App\Component\Image\Form\EditFormData;
use App\Component\Image\Form\FormFactory;
use App\Component\Image\ImageControl;
use App\Component\Image\ImageControlFactory;
use App\Component\Image\ImageFacade;
use App\Component\Translator\Translator;
use App\Core\Authenticator;
use App\Core\Enum\DefaultSnippetsEnum;
use App\Model\Language;
use App\Model\Module;
use App\Model\Setting;
use App\UI\Accessory\Form\Form;
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
    #[Inject]
    public FormFactory $formFactoryEditImage;
    #[Inject]
    public ImageFacade $imageFacade;

    protected function createComponentEditImageForm():Form{
        $form = $this->formFactoryEditImage->create();
        $form->onSuccess[] = function(Form $form, EditFormData $data):void{
            try {
                $this->imageFacade->edit($data);
                $this->flashMessage($this->translator->translate('flash_imageUpdated'));
            }catch (ImageNotFoundException $e){
                $this->flashMessage($this->translator->translate('flash_imageNotFound'), 'error');
            }
            $this->redrawControl(DefaultSnippetsEnum::Flashes->value);
        };
        return $form;
    }

    protected function createComponentImage():ImageControl
    {
        return $this->imageControlFactory->create();
    }

    public function injectStandardTemplate(ParameterBag $parameterBag, SubmenuFactory $submenuFactory, ImageFacade $imageFacade,
                                           Module $moduleModel, Language $languageModel, Authenticator $authenticator, Setting $settingModel
    ): void
    {
        $this->onRender[] = function () use ($parameterBag, $submenuFactory, $imageFacade, $moduleModel, $languageModel, $settingModel): void {
            $this->template->setTranslator($this->translator);
            $this->template->webpackVersion = md5(FileSystem::read($parameterBag->wwwDir.'/incore/version.txt'));
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
            $this->template->editedImage = null;
            $this->getUser()->setAuthenticator($authenticator);
        };
    }
}
