<?php

namespace App\UI\Accessory\Admin\Form\Controls\EditorJs;

interface EditorJsInputFactory
{
    public function create(?string $label): EditorJsInput;
}