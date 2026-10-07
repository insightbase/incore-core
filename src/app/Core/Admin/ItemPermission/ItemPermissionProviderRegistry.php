<?php

namespace App\Core\Admin\ItemPermission;

use Nette\DI\Container;

final class ItemPermissionProviderRegistry
{
    /** @var ?array<string, ItemPermissionProvider> */
    private ?array $providers = null;

    public function __construct(
        private readonly Container $container,
    ) {}

    public function get(string $moduleSystemName): ?ItemPermissionProvider
    {
        return $this->getProviders()[$moduleSystemName] ?? null;
    }

    public function has(string $moduleSystemName): bool
    {
        return $this->get($moduleSystemName) !== null;
    }

    /** @return array<string, ItemPermissionProvider> */
    private function getProviders(): array
    {
        if ($this->providers !== null) {
            return $this->providers;
        }

        $providers = [];
        foreach ($this->container->findByType(ItemPermissionProvider::class) as $serviceName) {
            /** @var ItemPermissionProvider $provider */
            $provider = $this->container->getService($serviceName);
            $module = $provider->getModuleSystemName();
            if (array_key_exists($module, $providers)) {
                throw new \LogicException(sprintf(
                    'Modul "%s" má dva providery položek: %s a %s.',
                    $module,
                    $providers[$module]::class,
                    $provider::class,
                ));
            }
            $providers[$module] = $provider;
        }

        return $this->providers = $providers;
    }
}
