<?php

namespace App\UI\Accessory\Admin\Form;

use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageInput;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageInputFactory;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageLocationEnum;

class Container extends \Nette\Forms\Container
{
    public function __construct(
        private readonly DropzoneImageInputFactory $dropzoneImageInputFactory,
    )
    {
    }

    public function addDropzoneImage(DropzoneImageLocationEnum $locationEnum, string $name, string $label): DropzoneImageInput
    {
        return $this[$name] = $this->dropzoneImageInputFactory->create($locationEnum, $label);
    }
}