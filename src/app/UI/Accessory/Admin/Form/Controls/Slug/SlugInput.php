<?php

namespace App\UI\Accessory\Admin\Form\Controls\Slug;

use Nette\Forms\Controls\TextBase;
use Nette\Utils\Html;

class SlugInput extends TextBase
{
    public function __construct(
        private ?TextBase $sourceInput,
        ?string $caption = null
    )
    {
        parent::__construct($caption);
    }

    public function getControl():Html
    {
        $input = parent::getControl();
        $input->setAttribute('data-source-input', $this->sourceInput->getHtmlId());
        return $input;
    }

    public function setSourceInput(?TextBase $sourceInput): self
    {
        $this->sourceInput = $sourceInput;
        return $this;
    }
}