<?php

namespace App\UI\Accessory\Admin\Form\Controls\EditorJs;

use Nette;
use Nette\Forms\Controls\TextInput;
use Stringable;

class EditorJsInput extends TextInput
{
    /**
     * @var ?EditorJsTypeEnum[]
     */
    public ?array $showType = null;

    public function __construct(
        private readonly Nette\Application\LinkGenerator $linkGenerator,
        Stringable|string|null                           $label = null, ?int $maxLength = null)
    {
        parent::__construct($label, $maxLength);
    }

    public function getControl(): Nette\Utils\Html
    {
        $control = parent::getControl();

        if($this->showType === null){
            foreach(EditorJsTypeEnum::cases() as $type){
                $this->showType[] = $type;
            }
        }

        $control->setAttribute('data-type', implode(';', Nette\Utils\Arrays::map($this->showType, fn($value) => $value->value)));
        $control->setAttribute('data-upload-url', $this->linkGenerator->link('Admin:EditorJs:upload'));
        return $control;
    }
}