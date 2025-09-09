<?php

namespace App\UI\Accessory\Admin\Form;

use App\Component\Translator\Translator;
use App\Model\Admin\FormHelp;
use App\Model\Admin\FormHelpLanguage;
use App\Model\Admin\Language;
use App\Model\Entity\LanguageEntity;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneFileInput;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneFileInputFactory;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageInput;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageInputFactory;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageLocationEnum;
use App\UI\Accessory\Admin\Form\Controls\EditorJs\EditorJsInput;
use App\UI\Accessory\Admin\Form\Controls\EditorJs\EditorJsInputFactory;
use Nette;

class Form extends Nette\Application\UI\Form
{
    public const string LANG_CHANGE_ATTRIBUTE = 'langChange';
    public bool $showHelp = true;

    private array $defaultTranslates = [];
    public bool $sendByAjax = false {
        get {
            return $this->sendByAjax;
        }
    }

    public function __construct(
        private readonly DropzoneImageInputFactory $dropzoneInputFactory,
        private readonly EditorJsInputFactory      $editorJsInputFactory,
        public readonly Language                   $languageModel,
        private readonly DropzoneFileInputFactory  $dropzoneFileInputFactory,
        private readonly ContainerFactory          $containerFactory,
        private readonly FormHelp                  $formHelpModel,
        private readonly FormHelpLanguage          $formHelpLanguageModel,
        private readonly Translator                $translator,
        ?Nette\ComponentModel\IContainer           $parent = null,
        ?string                                    $name = null
    ) {
        parent::__construct($parent, $name);
    }

    public function getHelpLabel(string $inputHtmlId):?string
    {
        $formHelp = $this->formHelpModel->getByPresenterAndInputHtmlId($this->getPresenter()->getName(), $inputHtmlId);
        if($formHelp !== null){
            $formHelpLanguage = $this->formHelpLanguageModel->getByFormHelpAndLanguage($formHelp, $this->translator->getLanguage());
            if($formHelpLanguage !== null && $formHelpLanguage->label_help !== null && $formHelpLanguage->label_help !== ''){
                return $formHelpLanguage->label_help;
            }
        }
        return $formHelp?->label_help;
    }

    public function addContainer(int|string $name): Nette\Forms\Container
    {
        $control = $this->containerFactory->create();
        $control->currentGroup = $this->currentGroup;
        $this->currentGroup?->add($control);
        return $this[$name] = $control;
    }

    /**
     * @param LanguageEntity $language
     */
    public function getTranslates(Nette\Database\Table\ActiveRow $language): array
    {
        return $this->getHttpData()['language'][$language->id];
    }

    public function parseStringToLinearArray(string $input): array
    {
        $pattern = '/([^\[\]]+)|\[(\d+|[^\[\]]+)\]/'; // Rozdělí řetězec na části
        preg_match_all($pattern, $input, $matches);

        $result = [];
        foreach ($matches[0] as $match) {
            if (preg_match('/^\[(\d+|[^\[\]]+)\]$/', $match, $indexMatch)) {
                $key = is_numeric($indexMatch[1]) ? (int) $indexMatch[1] : $indexMatch[1];
            } else {
                $key = $match;
            }
            $result[] = $key; // Přidá do výsledného pole
        }

        unset($result[count($result) - 1]);

        return $result;
    }

    public function addDropzoneImage(DropzoneImageLocationEnum $locationEnum, string $name, string $label): DropzoneImageInput
    {
        return $this[$name] = $this->dropzoneInputFactory->create($locationEnum, $label);
    }

    public function addDropzoneFile(string $name, string $label): DropzoneFileInput
    {
        return $this[$name] = $this->dropzoneFileInputFactory->create($label);
    }

    public function addEditorJs(string $name, string $label): EditorJsInput
    {
        $input = $this->editorJsInputFactory->create($label);
        $input->getControlPrototype()->class('editorJsText');
        return $this[$name] = $input;
    }

    /**
     * @param LanguageEntity $language
     */
    public function setTranslates(Nette\Database\Table\ActiveRow $language, array $values): self
    {
        if (!array_key_exists($language->id, $this->defaultTranslates)) {
            $this->defaultTranslates[$language->id] = [];
        }
        $this->defaultTranslates[$language->id] = $this->defaultTranslates[$language->id] + $values;

        return $this;
    }

    public function sendByAjax(bool $sendByAjax = true):self
    {
        $this->sendByAjax = $sendByAjax;
        return $this;
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
        foreach ($this->languageModel->getToTranslateNotDefault() as $language) {
            $languageContainer->addContainer($language->id);
            foreach ($controlsToTranslate as $input) {
                $lang = $languageContainer->getComponent($language->id);
                if (!array_key_exists($language->id, $this->defaultTranslates)) {
                    $defaults = null;
                } else {
                    $defaults = $this->defaultTranslates[$language->id];
                }
                $originalName = ['language', $language->id];

                if (!$input->getParent() instanceof Form) {
                    $route = [];

                    $container = $input->getParent();
                    while (!$container instanceof Form) {
                        $route[] = $container->getName();
                        $container = $container->getParent();
                    }
                    $route = array_reverse($route);
                    foreach ($route as $item) {
                        $originalName[] = $item;
                        if (null !== $defaults && array_key_exists($item, $defaults)) {
                            $defaults = $defaults[$item];
                        } else {
                            $defaults = null;
                        }

                        if (array_key_exists($item, $lang->getComponents())) {
                            $lang = $lang->getComponent($item);
                        } else {
                            $lang = $lang->addContainer($item);
                        }
                    }
                }

                $base = array_shift($originalName);
                foreach ($originalName as $item) {
                    $base .= "[{$item}]";
                }

                if($input instanceof EditorJsInput){
                    $input1 = $this->editorJsInputFactory->create(null);
                    $input1->getControlPrototype()->class('editorJsText');
                    $input1->setNullable()
                        ->setHtmlAttribute('data-language-id', $language->id)
                        ->setHtmlAttribute(self::LANG_CHANGE_ATTRIBUTE)
                        ->setDefaultValue(null !== $defaults && array_key_exists($input->getName(), $defaults) ? $defaults[$input->getName()] : null)
                        ->setHtmlAttribute('data-original-name', $base);
                    $lang[$input->getName()] = $input1;
                }else {
                    $lang->addText($input->getName())
                        ->setNullable()
                        ->setHtmlAttribute('data-language-id', $language->id)
                        ->setHtmlAttribute(self::LANG_CHANGE_ATTRIBUTE)
                        ->setDefaultValue(null !== $defaults && array_key_exists($input->getName(), $defaults) ? $defaults[$input->getName()] : null)
                        ->setHtmlAttribute('data-original-name', $base);
                }
            }
        }
    }
}
