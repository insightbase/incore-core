<?php

namespace App\Component\Credit;

use App\Component\DropCore\DropCoreConfig;
use App\Component\DropCore\DropCoreEnvEnum;
use App\Model\Admin\Setting;
use App\Model\Entity\SettingEntity;

readonly class CreditFacade
{
    public function __construct(
        private Setting $settingModel,
        private CreditClient $creditClient,
        private string $apiUrlDemo,
        private string $apiUrlProd,
    ) {}

    /**
     * Zůstatek kreditů. Null, když nastavení není kompletní nebo API selhalo.
     */
    public function getBalance(): ?int
    {
        /** @var ?SettingEntity $setting */
        $setting = $this->settingModel->getDefault();

        $account = $setting?->credit_id;
        $store = $setting?->dropcore_store;
        $accessToken = $setting?->dropcore_access_token;
        $apiUrl = match (DropCoreEnvEnum::tryFrom((string) $setting?->dropcore_env)) {
            DropCoreEnvEnum::Demo => $this->apiUrlDemo,
            DropCoreEnvEnum::Prod => $this->apiUrlProd,
            default => null,
        };

        if (
            null === $account || '' === $account
            || null === $store || '' === $store
            || null === $accessToken || '' === $accessToken
            || null === $apiUrl
        ) {
            return null;
        }

        return $this->creditClient->getBalance(new DropCoreConfig($apiUrl, $store, $accessToken), $account);
    }
}
