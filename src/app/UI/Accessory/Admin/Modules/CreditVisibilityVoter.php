<?php

namespace App\UI\Accessory\Admin\Modules;

use App\Component\DropCore\DropCoreConfigProvider;
use App\Model\Entity\ModuleEntity;
use Nette\Database\Table\ActiveRow;

class CreditVisibilityVoter implements VisibilityVoter
{
    private const string MODULE_CREDIT = 'credit';

    public function __construct(
        private readonly DropCoreConfigProvider $dropCoreConfigProvider,
    ) {}

    /**
     * @param ModuleEntity $module
     */
    public function isVisible(ActiveRow $module): bool
    {
        if (self::MODULE_CREDIT !== $module->system_name) {
            return true;
        }

        return null !== $this->dropCoreConfigProvider->getIdentityToken();
    }
}
