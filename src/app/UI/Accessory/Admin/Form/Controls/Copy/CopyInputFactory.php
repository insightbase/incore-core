<?php

namespace App\UI\Accessory\Admin\Form\Controls\Copy;

use Nette\Forms\Controls\TextInput;

interface CopyInputFactory
{
    public function create(?TextInput $sourceInput, ?string $caption): CopyInput;
}