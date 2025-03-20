<?php

namespace App\UI\Admin\Module;

use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Model\Entity\ModuleEntity;
use App\UI\Admin\Module\Form\EditData;
use Nette\Database\Table\ActiveRow;

readonly class ModuleFacade
{
    public function __construct(
        private LogFacade $logFacade,
    )
    {
    }

    /**
     * @param ModuleEntity $module
     * @param EditData $data
     * @return void
     */
    public function update(ActiveRow $module, EditData $data): void
    {
        $module->update((array) $data);
        $this->logFacade->create(LogActionEnum::Updated, 'module', $module->id);
    }
}
