<?php

namespace App\UI\Accessory\Admin\Form;

use Nette\Database\Table\ActiveRow;
use Nette\Forms\ControlGroup;

class GroupLanguage extends ControlGroup
{
    private ActiveRow $language;

    public function getLanguage():ActiveRow{
        return $this->language;
    }

    public function setLanguage(ActiveRow $language):self{
        $this->language = $language;
        return $this;
    }
}