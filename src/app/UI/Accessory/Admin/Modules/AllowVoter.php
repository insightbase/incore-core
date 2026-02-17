<?php

namespace App\UI\Accessory\Admin\Modules;

use App\Model\Entity\ModuleEntity;
use Nette\Database\Table\ActiveRow;

class AllowVoter implements VisibilityVoter
{

    /**
     * @param ModuleEntity $module
     * @return bool
     */
    public function isVisible(ActiveRow $module): bool
    {
        return true;
    }
}