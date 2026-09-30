<?php

namespace App\Component\DropCore;

use App\Model\Admin\Setting;
use App\Model\Admin\User;
use App\Model\Entity\SettingEntity;
use App\Model\Entity\UserEntity;
use App\UI\Accessory\ParameterBag;

readonly class DropCoreConfigProvider
{
    public function __construct(
        private Setting $settingModel,
        private User $userModel,
        private \Nette\Security\User $userSecurity,
        private ParameterBag $parameterBag,
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
        $identityToken = $this->getIdentityToken();
        $env = DropCoreEnvEnum::tryFrom((string) $setting?->dropcore_env);

        if ($env === DropCoreEnvEnum::Simulation) {
            // Simulace je jen pro vývoj - mimo debug režim se chová jako nevyplněné nastavení.
            return $this->parameterBag->debugMode
                ? new DropCoreConfig('', $store ?? 'simulation', $identityToken ?? 'simulation', simulation: true)
                : null;
        }

        $apiUrl = match ($env) {
            DropCoreEnvEnum::Demo => $this->apiUrlDemo,
            DropCoreEnvEnum::Prod => $this->apiUrlProd,
            default => null,
        };

        if (
            null === $apiUrl
            || null === $store || '' === $store
            || null === $identityToken
        ) {
            return null;
        }

        return new DropCoreConfig($apiUrl, $store, $identityToken);
    }

    /**
     * Identity token pro DropCore: osobní token přihlášeného uživatele, je-li vyplněn,
     * jinak token z nastavení. Null, když není ani jeden.
     */
    public function getIdentityToken(): ?string
    {
        $userId = $this->userSecurity->isLoggedIn() ? $this->userSecurity->getId() : null;
        if (null !== $userId) {
            /** @var ?UserEntity $user */
            $user = $this->userModel->get((int) $userId);
            $userToken = $user?->dropcore_identity_token;
            if (null !== $userToken && '' !== $userToken) {
                return $userToken;
            }
        }

        /** @var ?SettingEntity $setting */
        $setting = $this->settingModel->getDefault();
        $token = $setting?->dropcore_identity_token;

        return null !== $token && '' !== $token ? $token : null;
    }
}
