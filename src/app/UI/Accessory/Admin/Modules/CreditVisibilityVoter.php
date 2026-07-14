<?php

namespace App\UI\Accessory\Admin\Modules;

use App\Model\Admin\Setting;
use App\Model\Entity\ModuleEntity;
use App\Model\Entity\SettingEntity;
use Nette\Database\Table\ActiveRow;

class CreditVisibilityVoter implements VisibilityVoter
{
    private const string MODULE_CREDIT = 'credit';

    public function __construct(
        private readonly Setting $settingModel,
    ) {}

    /**
     * @param ModuleEntity $module
     */
    public function isVisible(ActiveRow $module): bool
    {
        if (self::MODULE_CREDIT !== $module->system_name) {
            return true;
        }

        /** @var ?SettingEntity $setting */
        $setting = $this->settingModel->getDefault();
        $token = $setting?->dropcore_identity_token;

        return null !== $token && '' !== $token;
    }
}
