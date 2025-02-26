<?php
namespace App\UI\Accessory\Front\PresenterTrait;

use App\Component\Translator\Translator;
use Nette\Application\Attributes\Persistent;
use Nette\Bridges\SecurityHttp\SessionStorage;
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

    public function injectStandardTemplate():void{
        $this->onRender[] = function ():void{
            $this->template->setTranslator($this->translator);
        };
        $this->onStartup[] = function ():void{
            $this->translator->setLang($this->lang);
            $storage = $this->getUser()->getStorage();
            if ($storage instanceof SessionStorage) {
                $storage->setNamespace('admin');
            }
        };
    }
}