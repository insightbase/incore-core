<?php

namespace App\UI\Accessory\Admin\Form\Controls\Options;

use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;

class OptionsInput extends TextInput
{
    /**
     * @var string[]
     */
    private array $showForTypes = [];

    public function __construct(?string $caption = null)
    {
        parent::__construct($caption);
    }

    public function getControl(): Html
    {
        $input = parent::getControl();
        $input->setAttribute('data-options-widget', '1');
        if ($this->showForTypes !== []) {
            $input->setAttribute('data-options-for', implode(',', $this->showForTypes));
        }
        return $input;
    }

    /**
     * @param string[] $types
     */
    public function setShowForTypes(array $types): self
    {
        $this->showForTypes = $types;
        return $this;
    }
}
