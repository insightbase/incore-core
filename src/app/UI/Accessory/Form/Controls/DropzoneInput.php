<?php

namespace App\UI\Accessory\Form\Controls;

use App\Component\Image\ImageControlFactory;
use App\Component\Image\ImageFacade;
use App\Component\Translator\Translator;
use Nette;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;

class DropzoneInput extends TextInput
{
    public function __construct(
        private readonly Nette\Application\LinkGenerator $linkGenerator,
        private readonly ImageControlFactory             $imageControlFactory,
        null|string|\Stringable                          $label = null,
        ?int                                             $maxLength = null
    ) {
        parent::__construct($label, $maxLength);
    }

    public function getControl(): Html
    {
        $image = Html::el();
        if ($this->getValue()) {
            $imageControlFactory = clone $this->imageControlFactory;
            $imageControlFactory->setParent($this->getForm()->getPresenter());
            $image = Html::el('div')->class('dz-preview dz-file-preview')
                ->addHtml(Html::el('div')->class('dz-image')->addHtml($imageControlFactory->renderToString($this->getValue(), 100, 100)))
            ;
        }

        $container = Html::el();
        $dropzone = Html::el('div')
            ->setClass('dropzone')
            ->setAttribute('data-upload-url', $this->linkGenerator->link('Image:upload'))
            ->addHtml(Html::el('div')->class('ms-4')->addHtml($image))
        ;

        $container->addHtml(parent::getControl()->style('display: none'));
        $container->addHtml($dropzone);

        return $container;
    }
}
