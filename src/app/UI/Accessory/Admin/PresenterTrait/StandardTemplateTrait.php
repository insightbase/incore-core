<?php

namespace App\UI\Accessory\Admin\PresenterTrait;

use App\Component\Image\Exception\ImageNotFoundException;
use App\Component\Image\Form\EditFormData;
use App\Component\Image\Form\FormFactory;
use App\Component\Image\ImageControl;
use App\Component\Image\ImageControlFactory;
use App\Component\Image\ImageFacade;
use App\Component\Translator\Translator;
use App\Core\Admin\Authenticator;
use App\Core\Admin\AuthorizatorFactory;
use App\Core\Admin\Enum\DefaultSnippetsEnum;
use App\Model\Admin\Image;
use App\Model\Admin\Language;
use App\Model\Admin\Module;
use App\Model\Admin\Setting;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\MainMenu\MainMenuFactory;
use App\UI\Accessory\Admin\Submenu\SubmenuFactory;
use App\UI\Accessory\ParameterBag;
use App\UI\Admin\BaseTemplate;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\Attributes\Persistent;
use Nette\Application\Attributes\Requires;
use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette\DI\Attributes\Inject;
use Nette\Security\Authorizator;
use Nette\Utils\Arrays;
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
    #[Inject]
    public Image $imageModel;

    #[Requires(ajax: true)]
    #[NoReturn] public function handleUpdateEditImageForm(int $imageId):void
    {
        $image = $this->imageModel->get($imageId);
        if($image === null){
            $this->error($this->translator->translate('flash_imageNotFound'));
        }
        $this->presenter->getTemplate()->editedImage = $image;
        $this->getPresenter()->getComponent('editImageForm')->setDefaults([
            'alt' => $image->alt,
            'name' => $image->name,
            'description' => $image->description,
            'author' => $image->author,
            'image_id' => $image->id,
        ]);
        $this->getPresenter()->redrawControl('editImageForm');
    }

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
                                           Module $moduleModel, Language $languageModel, Authenticator $authenticator, Setting $settingModel,
                                           AuthorizatorFactory $authorizatorFactory,
    ): void
    {
        $this->onRender[] = function () use ($parameterBag, $submenuFactory, $imageFacade, $moduleModel, $languageModel, $settingModel): void {
            $this->template->setTranslator($this->translator);
            if(file_exists($parameterBag->wwwDir.'/incore/version.txt')) {
                $this->template->webpackVersion = md5(FileSystem::read($parameterBag->wwwDir . '/incore/version.txt'));
            }else{
                $this->template->webpackVersion = md5(time());
            }
            $this->template->submenuFactory = $submenuFactory;
            $this->template->layoutFile = dirname(__FILE__).'/../../../Admin/@layout.latte';
            $this->template->basicFormFile = dirname(__FILE__).'/../Form/basic-form.latte';
            $this->template->basicModalFile = dirname(__FILE__).'/../Modal/basic-modal.latte';
            $this->template->imageFacade = $imageFacade;
            $this->template->menuModules = $moduleModel->getToMenu();
            $this->template->moduleModel = $moduleModel;
            $this->template->languages = $languages = $languageModel->getToTranslate();
            $this->template->moduleTree = $moduleTree = $moduleModel->getTree($this->getName());
            $this->template->mainMenuFactory = $this->mainMenuFactory;
            $this->template->setting = $settingModel->getDefault();
            $this->template->metronicDir = $parameterBag->metronicDir;
            $showSubmenuDropdown = false;
            foreach($submenuFactory->getSubMenus() as $subMenuItem){
                if($subMenuItem->isShowInDropdown() && $this->user->isAllowed(Arrays::last($moduleTree)->system_name, $subMenuItem->getAction())){
                    $showSubmenuDropdown = true;
                    break;
                }
            }
            $this->template->showSubmenuDropdown = $showSubmenuDropdown;
            foreach ($languages as $language) {
                if ($language->is_default) {
                    $this->template->defaultLanguage = $language;

                    break;
                }
            }
        };
        $this->onStartup[] = function () use ($authenticator, $authorizatorFactory): void {
            $this->translator->setLang($this->lang);
            $this->template->editedImage = null;
            $this->getUser()->setAuthenticator($authenticator);
            $this->getUser()->setAuthorizator($authorizatorFactory->create());
            $storage = $this->getUser()->getStorage();
            if ($storage instanceof SessionStorage) {
                $storage->setNamespace('admin');
            }
        };
    }
}
