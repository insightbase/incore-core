<?php

namespace App\UI\Accessory\Form\Controls;

use App\Component\Image\ImageFacade;
use App\Component\Translator\Translator;
use Nette;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;

class DropzoneInput extends TextInput
{
    public function __construct(
        private Nette\Application\LinkGenerator $linkGenerator,
        private ImageFacade $imageFacade,
        private Translator $translator,
        null|string|\Stringable $label = null,
        ?int $maxLength = null
    ) {
        parent::__construct($label, $maxLength);
    }

    public function getControl(): Html
    {
        $image = Html::el();
        if ($this->getValue()) {
            $image = Html::el('div')->class('dz-preview dz-file-preview')
                ->addHtml(Html::el('div')->class('dz-image')->addHtml(Html::el('img')->src($this->imageFacade->preview($this->getValue(), 100, 100))))
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
