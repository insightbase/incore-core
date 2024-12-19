<?php

namespace App\UI\Accessory\Form\Controls;

interface DropzoneInputFactory
{
    public function create(string $label):DropzoneInput;
}