<?php

namespace App\UI\Accessory\Admin\Form\Controls\Copy;

use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;

class CopyInput extends TextInput
{
    public function __construct(
        private ?TextInput $sourceInput,
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

    public function setSourceInput(?TextInput $sourceInput): self
    {
        $this->sourceInput = $sourceInput;
        return $this;
    }
}