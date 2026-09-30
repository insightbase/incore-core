<?php

namespace App\Component\DropCore;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Callbacky DropCore, které dorazily dřív, než se k dávce uložilo její drop_core_id
 * (rychlé demo DropCore odpoví callbackem ještě před odpovědí na samotný požadavek).
 * Odesílání dávky si dokončení po uložení drop_core_id převezme (TranslationBatchSender).
 */
final readonly class DropCoreEarlyCallbackStore
{
    private const string CACHE_NAMESPACE = 'dropCoreEarlyCallback';

    public function __construct(
        private Storage $storage,
    ) {}

    public function remember(string $dropCoreId, \DateTimeInterface $finished): void
    {
        $this->getCache()->save($dropCoreId, $finished, [Cache::Expire => '1 day']);
    }

    /**
     * Vrátí čas dokončení dávky, pokud její callback už dorazil, a záznam smaže.
     */
    public function take(string $dropCoreId): ?\DateTimeInterface
    {
        $cache = $this->getCache();
        $finished = $cache->load($dropCoreId);
        if ($finished === null) {
            return null;
        }

        $cache->remove($dropCoreId);

        return $finished instanceof \DateTimeInterface ? $finished : null;
    }

    private function getCache(): Cache
    {
        return new Cache($this->storage, self::CACHE_NAMESPACE);
    }
}
