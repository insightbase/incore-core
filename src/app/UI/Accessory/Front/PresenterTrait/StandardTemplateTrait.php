<?php
namespace App\UI\Accessory\Front\PresenterTrait;

use App\Component\Image\ImageControl;
use App\Component\Image\ImageControlFactory;
use App\Component\Translator\Translator;
use App\Model\Admin\Setting;
use Nette\Application\Attributes\Persistent;
use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette\Database\Table\ActiveRow;
use Nette\DI\Attributes\Inject;

/**
 * @property \App\UI\Front\BaseTemplate $template
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

    protected function createComponentImage():ImageControl
    {
        return $this->imageControlFactory->create();
    }

    public function injectStandardTemplate(Setting $settingModel):void{
        $this->onRender[] = function ():void{
            $this->template->setTranslator($this->translator);
            $this->template->setting = $this->setting;
            $this->template->imageControl = $this->imageControlFactory->create();
        };
        $this->onStartup[] = function () use ($settingModel):void{
            $this->setting = $settingModel->getDefault();
            $this->translator->setLang($this->lang);
            $storage = $this->getUser()->getStorage();
            if ($storage instanceof SessionStorage) {
                $storage->setNamespace('admin');
            }
        };
    }
}