<?php
namespace App\UI\Accessory\Front\PresenterTrait;

use App\Component\File\FileControl;
use App\Component\File\FileControlFactory;
use App\Component\Front\FaviconControl\FaviconControl;
use App\Component\Front\FaviconControl\FaviconControlFactory;
use App\Component\Image\ImageControl;
use App\Component\Image\ImageControlFactory;
use App\Component\Translator\Translator;
use App\Model\Admin\Setting;
use App\UI\Front\BaseTemplate;
use Nette\Application\Attributes\Persistent;
use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette\Database\Table\ActiveRow;
use Nette\DI\Attributes\Inject;

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
    public ImageControlFactory $imageControlFactory;
    protected ?ActiveRow $setting;
    #[Inject]
    public FaviconControlFactory $faviconControlFactory;
    #[Inject]
    public FileControlFactory $fileControlFactory;

    protected function createComponentImage():ImageControl
    {
        return $this->imageControlFactory->create();
    }

    protected function createComponentFavicon():FaviconControl{
        return $this->faviconControlFactory->create();
    }

    public function injectStandardTemplate(Setting $settingModel):void{
        $this->onRender[] = function ():void{
            $this->template->setTranslator($this->translator);
            $this->template->setting = $this->setting;
            $this->template->imageControl = $this->imageControlFactory->create();
            $this->template->fileControl = $this->fileControlFactory->create();
        };
        $this->onStartup[] = function () use ($settingModel):void{
            $this->setting = $settingModel->getDefault();
            $this->translator->setLang($this->lang);
            $storage = $this->getUser()->getStorage();
            if ($storage instanceof SessionStorage) {
                $storage->setNamespace('front');
            }
        };
    }
}