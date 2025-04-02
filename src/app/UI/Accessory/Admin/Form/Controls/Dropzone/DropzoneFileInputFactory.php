<?php

namespace App\UI\Accessory\Admin\Form\Controls\Dropzone;

interface DropzoneFileInputFactory
{
    public function create(string $label): DropzoneFileInput;
}