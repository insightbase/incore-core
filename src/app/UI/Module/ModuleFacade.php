<?php

namespace App\UI\Module;

use App\UI\Module\Form\EditData;
use Nette\Database\Table\ActiveRow;

class ModuleFacade
{
    public function update(ActiveRow $module, EditData $data): void
    {
        $module->update((array) $data);
    }
}
