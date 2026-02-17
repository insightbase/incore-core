<?php

namespace App\UI\Accessory\Admin\Modules;

use Nette\Database\Table\ActiveRow;

interface VisibilityVoter
{
    public function isVisible(ActiveRow $module): bool;
}