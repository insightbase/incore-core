<?php

namespace App\UI\Accessory\Admin\Form\Controls\TextAreaCopy;

use Nette\Forms\Controls\TextArea;
use Nette\Utils\Html;

class TextAreaCopyInput extends TextArea
{
    public function __construct(
        private ?TextArea $sourceInput,
        ?string $caption = null
    )
    {
        parent::__construct($caption);
    }

    public function getControl():Html
    {
        $input = parent::getControl();
        $input->setAttribute('data-source-copy-input', $this->sourceInput->getHtmlId());
        return $input;
    }

    public function setSourceInput(?TextArea $sourceInput): self
    {
        $this->sourceInput = $sourceInput;
        return $this;
    }
}