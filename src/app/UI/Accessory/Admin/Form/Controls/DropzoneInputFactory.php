<?php

namespace App\UI\Accessory\Admin\Form\Controls;

interface DropzoneInputFactory
{
    public function create(string $label): DropzoneInput;
}
