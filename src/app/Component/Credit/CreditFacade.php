<?php

namespace App\Component\Credit;

use App\Component\DropCore\DropCoreConfigProvider;
use App\Model\Admin\Setting;
use App\Model\Entity\SettingEntity;

readonly class CreditFacade
{
    public function __construct(
        private Setting $settingModel,
        private CreditClient $creditClient,
        private DropCoreConfigProvider $configProvider,
    ) {}

    /**
     * Zůstatek kreditů. Null, když nastavení není kompletní nebo API selhalo.
     */
    public function getBalance(): ?int
    {
        /** @var ?SettingEntity $setting */
        $setting = $this->settingModel->getDefault();

        $account = $setting?->credit_id;
        if (null === $account || '' === $account) {
            return null;
        }

        $config = $this->configProvider->getConfig();
        if (null === $config) {
            return null;
        }

        return $this->creditClient->getBalance($config, $account);
    }
}
