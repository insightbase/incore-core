<?php

namespace App\UI\Accessory\Admin\Form;

use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageInput;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageInputFactory;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageLocationEnum;
use App\UI\Accessory\Admin\Form\Controls\EditorJs\EditorJsInput;
use App\UI\Accessory\Admin\Form\Controls\EditorJs\EditorJsInputFactory;

class Container extends \Nette\Forms\Container
{
    public function __construct(
        private readonly DropzoneImageInputFactory $dropzoneImageInputFactory,
        private readonly EditorJsInputFactory      $editorJsInputFactory,
        private readonly ContainerFactory          $containerFactory,
    )
    {
    }

    public function addContainer(string|int $name): self
    {
        $control = $this->containerFactory->create();
        $control->currentGroup = $this->currentGroup;
        $this->currentGroup?->add($control);
        return $this[$name] = $control;
    }

    public function addDropzoneImage(DropzoneImageLocationEnum $locationEnum, string $name, string $label): DropzoneImageInput
    {
        return $this[$name] = $this->dropzoneImageInputFactory->create($locationEnum, $label);
    }

    public function addEditorJs(string $name, string $label): EditorJsInput
    {
        $input = $this->editorJsInputFactory->create($label);
        $input->getControlPrototype()->class('editorJsText');
        return $this[$name] = $input;
    }
}