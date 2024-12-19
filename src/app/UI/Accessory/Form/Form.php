<?php

namespace App\UI\Accessory\Form;

use App\UI\Accessory\Form\Controls\DropzoneInput;
use App\UI\Accessory\Form\Controls\DropzoneInputFactory;
use Nette;

class Form extends \Nette\Application\UI\Form
{
    public function __construct(
        private DropzoneInputFactory $dropzoneInputFactory,
        ?Nette\ComponentModel\IContainer $parent = null, ?string $name = null
    )
    {
        parent::__construct($parent, $name);
    }

    public function addDropzone(string $name, string $label):DropzoneInput
    {
        return $this[$name] = ($this->dropzoneInputFactory->create($label));
    }
}