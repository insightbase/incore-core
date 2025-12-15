<?php

namespace App\UI\Accessory\Admin\Form\Controls\TextAreaCopy;

use Nette\Forms\Controls\TextArea;

interface TextAreaCopyFactory
{
    public function create(?TextArea $sourceInput, ?string $caption): TextAreaCopyInput;
}