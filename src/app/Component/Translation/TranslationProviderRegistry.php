<?php

namespace App\Component\Translation;

use App\Component\Translation\Exception\DuplicateSystemNameException;
use App\Component\Translation\Exception\InvalidSystemNameException;
use App\Model\Entity\LanguageEntity;
use Nette\Database\Table\ActiveRow;
use Nette\DI\Container;

final class TranslationProviderRegistry
{
    /** @var ?array<string, TranslationProvider> */
    private ?array $providers = null;

    public function __construct(
        private readonly Container $container,
    ) {}

    public function get(string $systemName): ?TranslationProvider
    {
        return $this->getProviders()[$systemName] ?? null;
    }

    /** @return TranslationProvider[] */
    public function all(): array
    {
        return array_values($this->getProviders());
    }

    /**
     * Sesbírá texty všech providerů do pole připraveného k odeslání.
     * S `$onlyMissing` vynechá položky, které už zdroj (TranslatedItemsProvider)
     * v cílovém jazyce přeložené má.
     *
     * @param LanguageEntity $language
     * @return array<string, string|array<string, mixed>>
     */
    public function collectAll(ActiveRow $language, bool $onlyMissing = false): array
    {
        $json = [];
        foreach ($this->getProviders() as $systemName => $provider) {
            try {
                $items = $provider->collect($language);
                $translated = $onlyMissing && $provider instanceof TranslatedItemsProvider
                    ? $provider->getTranslatedItems($language)
                    : [];
            } catch (\Throwable $e) {
                // Zdroj (i z cizí aplikace) nesmí pádem shodit hromadný překlad celého webu -
                // jeho texty jen vynecháme z dávky a chybu zalogujeme, ostatní zdroje pokračují dál.
                \Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);

                continue;
            }

            foreach ($items as $item) {
                if (in_array($item->field, $translated[$item->id] ?? [], true)) {
                    continue;
                }

                $json[TranslationKey::encode($systemName, $item->id, $item->field)] = $item->value;
            }
        }

        return $json;
    }

    /** @return array<string, TranslationProvider> */
    private function getProviders(): array
    {
        if ($this->providers !== null) {
            return $this->providers;
        }

        $providers = [];
        foreach ($this->container->findByType(TranslationProvider::class) as $serviceName) {
            /** @var TranslationProvider $provider */
            $provider = $this->container->getService($serviceName);
            $systemName = $provider->getSystemName();

            if (!TranslationKey::isValidSystemName($systemName)) {
                throw new InvalidSystemNameException(
                    sprintf('Jméno zdroje překladu "%s" v %s neodpovídá tvaru ^[a-z][a-zA-Z0-9]*$.', $systemName, $provider::class),
                );
            }
            if (array_key_exists($systemName, $providers)) {
                throw new DuplicateSystemNameException(
                    sprintf('Jméno zdroje překladu "%s" používá %s i %s.', $systemName, $providers[$systemName]::class, $provider::class),
                );
            }

            $providers[$systemName] = $provider;
        }

        return $this->providers = $providers;
    }
}
