<?php

namespace App\UI\Accessory\Admin\Form\Controls\EditorJs;

use Nette;
use Nette\Forms\Controls\TextInput;

class EditorJsInput extends TextInput
{
    /**
     * @var ?EditorJsTypeEnum[]
     */
    public ?array $showType = null;

    public function getControl(): Nette\Utils\Html
    {
        $control = parent::getControl();

        if($this->showType === null){
            foreach(EditorJsTypeEnum::cases() as $type){
                $this->showType[] = $type;
            }
        }

        $control->setAttribute('data-type', implode(';', Nette\Utils\Arrays::map($this->showType, fn($value) => $value->value)));
        return $control;
    }
}