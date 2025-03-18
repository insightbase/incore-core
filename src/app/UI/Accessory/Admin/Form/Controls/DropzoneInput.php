<?php

namespace App\UI\Accessory\Admin\Form\Controls;

use App\Component\Image\ImageControlFactory;
use App\Model\Admin\Image;
use App\UI\Accessory\Admin\Form\Form;
use Nette;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;

class DropzoneInput extends TextInput
{
    public bool $multiple = false {
        get {
            return $this->multiple;
        }
        set {
            $this->multiple = $value;
        }
    }

    public function __construct(
        private readonly Nette\Application\LinkGenerator $linkGenerator,
        private readonly ImageControlFactory             $imageControlFactory,
        private readonly Image                           $imageModel,
        null|string|\Stringable                          $label = null,
        ?int                                             $maxLength = null
    ) {
        parent::__construct($label, $maxLength);
    }

    /**
     * @return null|int|int[]
     */
    public function getValue(): null|int|array
    {
        if(parent::getValue() === '' || parent::getValue() === null){
            return null;
        }
        if($this->multiple){
            return explode(';', parent::getValue());
        }else{
            return (int)parent::getValue();
        }
    }

    public function getControlDropzone(?string $class, TextInput $input): Html
    {
        $image = Html::el();
        if ($input->getValue()) {
            $imageControl = $this->imageControlFactory->create()->setParent($this->getForm()->getPresenter());
            $imageRow = $this->imageModel->get($input->getValue());
            if($imageRow !== null) {
                $image = Html::el('div')->class('dz-preview dz-file-preview')
                    ->addHtml(Html::el('div')->class('dz-image')->addHtml($imageControl->renderToString($input->getValue(), 100, 100)));
            }
        }

        $langChange = $input->getControl()->getAttribute(Form::LANG_CHANGE_ATTRIBUTE);

        $container = Html::el('div')->class($class . ' dropzoneContainer');
        if($langChange !== null){
            $container->setAttribute(Form::LANG_CHANGE_ATTRIBUTE, true);
            $container->setAttribute('data-language-id', $input->getControl()->getAttribute('data-language-id'));
        }
        $dropzone = Html::el('div')
            ->setClass('dropzone')
            ->setAttribute('data-upload-url', $this->linkGenerator->link('Admin:Image:upload'))
            ->addHtml(Html::el('div')->class('ms-4')->addHtml($image))
        ;
        $dropzone->setAttribute('data-multiple', $this->multiple);

        $container->addHtml($input->getControl()->style('display: none'));
        $container->addHtml($dropzone);

        return $container;
    }
}
