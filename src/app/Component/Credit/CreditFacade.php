<?php

namespace App\Component\Credit;

use App\Model\Admin\Setting;
use App\Model\Entity\SettingEntity;

readonly class CreditFacade
{
    public function __construct(
        private Setting $settingModel,
        private CreditClient $creditClient,
    ) {}

    /**
     * Zůstatek kreditů. Null, když účet není vyplněn nebo API selhalo.
     */
    public function getBalance(): ?int
    {
        /** @var ?SettingEntity $setting */
        $setting = $this->settingModel->getDefault();
        $account = $setting?->credit_id;

        if (null === $account || '' === $account) {
            return null;
        }

        return $this->creditClient->getBalance($account);
    }
}
