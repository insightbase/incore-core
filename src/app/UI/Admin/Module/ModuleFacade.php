<?php

namespace App\UI\Admin\Module;

use App\UI\Admin\Module\Form\EditData;
use Nette\Database\Table\ActiveRow;

class ModuleFacade
{
    public function update(ActiveRow $module, EditData $data): void
    {
        $module->update((array) $data);
    }
}
