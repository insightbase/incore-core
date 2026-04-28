<?php

namespace App\UI\Accessory\Admin\Form\Controls\Options;

interface OptionsInputFactory
{
    public function create(?string $caption): OptionsInput;
}
