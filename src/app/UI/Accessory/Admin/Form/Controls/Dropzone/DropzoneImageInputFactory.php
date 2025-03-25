<?php

namespace App\UI\Accessory\Admin\Form\Controls\Dropzone;

interface DropzoneImageInputFactory
{
    public function create(DropzoneImageLocationEnum $locationEnum, string $label): DropzoneImageInput;
}
