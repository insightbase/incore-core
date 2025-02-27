<?php

namespace App\UI\Accessory\Admin\Form\Controls;

interface EditorJsInputFactory
{
    public function create(?string $label): EditorJsInput;
}