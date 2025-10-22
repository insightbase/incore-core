<?php

namespace App\UI\Accessory\Admin\Form\Controls\Slug;

use Nette\Forms\Controls\TextBase;

interface SlugInputFactory
{
    public function create(?TextBase $sourceInput, ?string $caption): SlugInput;
}