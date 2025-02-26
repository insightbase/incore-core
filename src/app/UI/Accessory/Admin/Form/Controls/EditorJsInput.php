<?php

namespace App\UI\Accessory\Admin\Form\Controls;

use Nette;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;

class EditorJsInput extends TextInput
{
    public function getControl(): Nette\Utils\Html
    {
        $container = Html::el();

        $editor = Html::el('div')->class('editorJsHolder');

        $container->addHtml(parent::getControl()->style('display: none'));
        $container->addHtml($editor);

        return $container;
    }
}