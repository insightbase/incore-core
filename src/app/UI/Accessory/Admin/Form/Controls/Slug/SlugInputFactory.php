<?php

namespace App\UI\Accessory\Admin\Form\Controls\Slug;

use Nette\Forms\Controls\TextInput;

interface SlugInputFactory
{
    public function create(?TextInput $sourceInput, ?string $caption): SlugInput;
}