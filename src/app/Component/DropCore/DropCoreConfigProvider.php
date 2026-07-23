<?php

namespace App\Component\DropCore;

use App\Model\Admin\Setting;
use App\Model\Entity\SettingEntity;

readonly class DropCoreConfigProvider
{
    public function __construct(
        private Setting $settingModel,
        private string $apiUrlDemo,
        private string $apiUrlProd,
    ) {}

    /**
     * DropCore API konfigurace z výchozího nastavení (URL dle prostředí, store, identity token).
     * Null, když nastavení není kompletní — sdílené kredity i překlady.
     */
    public function getConfig(): ?DropCoreConfig
    {
        /** @var ?SettingEntity $setting */
        $setting = $this->settingModel->getDefault();

        $store = $setting?->dropcore_store;
        $identityToken = $setting?->dropcore_identity_token;
        $apiUrl = match (DropCoreEnvEnum::tryFrom((string) $setting?->dropcore_env)) {
            DropCoreEnvEnum::Demo => $this->apiUrlDemo,
            DropCoreEnvEnum::Prod => $this->apiUrlProd,
            default => null,
        };

        if (
            null === $apiUrl
            || null === $store || '' === $store
            || null === $identityToken || '' === $identityToken
        ) {
            return null;
        }

        return new DropCoreConfig($apiUrl, $store, $identityToken);
    }
}
