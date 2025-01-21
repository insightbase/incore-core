<?php

namespace App\UI\Accessory\Form;

use App\Model\Entity\LanguageEntity;
use App\Model\Language;
use App\UI\Accessory\Form\Controls\DropzoneInput;
use App\UI\Accessory\Form\Controls\DropzoneInputFactory;
use Nette;

class Form extends \Nette\Application\UI\Form
{
    public const LANG_CHANGE_ATTRIBUTE = 'langChange';

    public function __construct(
        private readonly DropzoneInputFactory $dropzoneInputFactory,
        public readonly Language             $languageModel,
        ?Nette\ComponentModel\IContainer      $parent = null, ?string $name = null
    )
    {
        parent::__construct($parent, $name);
    }

    public function addDropzone(string $name, string $label):DropzoneInput
    {
        return $this[$name] = ($this->dropzoneInputFactory->create($label));
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $controlsToTranslate = [];
        foreach ($this->getControls() as $input) {
            if ($input->getControl()->getAttribute(self::LANG_CHANGE_ATTRIBUTE)) {
                $controlsToTranslate[] = $input;
            }
        }

        $languageContainer = $this->addContainer('language');
        foreach($this->languageModel->getToTranslateNotDefault() as $language) {
            $lang = $languageContainer->addContainer($language->id);
            foreach($controlsToTranslate as $input) {
                if(!($input->getParent() instanceof Form)){
                    $route = [];

                    $container = $input->getParent();
                    while(!($container instanceof Form)){
                        $route[] = $container->getName();
                        $container = $container->getParent();
                    }
                    $route = array_reverse($route);
                    foreach($route as $item){
                        $lang = $lang->addContainer($item);
                    }
                }

                $lang->addText($input->getName())
                    ->setHtmlAttribute('data-language-id', $language->id)
                    ->setHtmlAttribute(self::LANG_CHANGE_ATTRIBUTE)
                ;
            }
        }
    }

    /**
     * @param Nette\Forms\Controls\BaseControl $input
     * @param Nette\Forms\Container $container
     * @param LanguageEntity $language
     * @return void
     */
    private function addInputTranslate(Nette\Forms\Controls\BaseControl $input, Nette\Forms\Container $container, Nette\Database\Table\ActiveRow $language):void{
        if($input->getParent() instanceof Form){
            $container->addText($input->getName())
                ->setHtmlAttribute('data-language-id', $language->id)
                ->setHtmlAttribute(self::LANG_CHANGE_ATTRIBUTE)
            ;
        }else{
            $container = $container->addContainer($input->getParent()->getName());
            $this->addInputTranslate($container);
        }
    }
}