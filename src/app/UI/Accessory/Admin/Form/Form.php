<?php

namespace App\UI\Accessory\Admin\Form;

use App\Model\Admin\Language;
use App\Model\Entity\LanguageEntity;
use App\UI\Accessory\Admin\Form\Controls\DropzoneInput;
use App\UI\Accessory\Admin\Form\Controls\DropzoneInputFactory;
use App\UI\Accessory\Admin\Form\Controls\EditorJsInputFactory;
use App\UI\Accessory\Admin\Form\Controls\EditorJsInput;
use Nette;

class Form extends Nette\Application\UI\Form
{
    public const string LANG_CHANGE_ATTRIBUTE = 'langChange';

    private array $defaultTranslates = [];
    public bool $sendByAjax = false {
        get {
            return $this->sendByAjax;
        }
    }

    public function __construct(
        private readonly DropzoneInputFactory $dropzoneInputFactory,
        private readonly EditorJsInputFactory $editorJsInputFactory,
        public readonly Language              $languageModel,
        ?Nette\ComponentModel\IContainer      $parent = null,
        ?string                               $name = null
    ) {
        parent::__construct($parent, $name);
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

    public function addDropzone(string $name, string $label): DropzoneInput
    {
        return $this[$name] = $this->dropzoneInputFactory->create($label)->addRule($this::Integer);
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
