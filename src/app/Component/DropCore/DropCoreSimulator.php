<?php

namespace App\Component\DropCore;

use App\UI\Admin\Language\Exception\NotEnoughCreditsException;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Random;

/**
 * Simulace DropCore pro vývoj (prostředí „simulation“ v nastavení, jen v debug režimu).
 *
 * Dávka se místo odeslání do DropCore zařadí do fronty v cache. Po prodlevě ji
 * `processDue()` „přeloží“ - k textům připíše značku jazyka, např. „[EN] Popis“ -
 * a vrátí stejnou cestou jako skutečný callback (LanguageFacade::processDropCoreCallback()).
 * Frontu zpracovává polling lišty s průběhem (TranslationJob:status), takže není
 * potřeba žádný worker ani cron.
 */
final readonly class DropCoreSimulator
{
    private const string CACHE_NAMESPACE = 'dropCoreSimulation';
    private const string CACHE_KEY = 'queue';

    /**
     * Klíče, pod kterými má EditorJs (a podobné JSON struktury) text k překladu.
     * Ostatní hodnoty (type, id, level, style…) jsou technické a zůstanou beze změny,
     * stejně jako je DropCore nepřekládá.
     */
    private const array TEXT_KEYS = ['text', 'caption', 'title', 'message', 'items', 'content'];

    /**
     * @param int $delay prodleva v sekundách, než se dávka „přeloží“ (dávky se vracejí postupně)
     * @param ?int $failAtBatch pořadí dávky (od 1) v jednom odeslání, u které simulace
     *                          nahlásí nedostatek kreditů; null = bez chyby
     */
    public function __construct(
        private Storage $storage,
        private int $delay = 5,
        private ?int $failAtBatch = null,
    ) {}

    /**
     * Zařadí dávku do fronty a vrátí její ID, jako by ho vrátil DropCore.
     *
     * @param array<string, mixed> $value
     * @throws NotEnoughCreditsException simulovaná chyba podle $failAtBatch
     */
    public function enqueue(array $value, int $languageId, string $outputLocale, int $batchIndex, int $totalBatches): string
    {
        if ($this->failAtBatch !== null && $batchIndex + 1 === $this->failAtBatch) {
            throw new NotEnoughCreditsException('Simulace: na překlad není dostatek kreditů.', $batchIndex, $totalBatches);
        }

        $cache = $this->getCache();
        $queue = $this->loadQueue($cache);

        // Dávky se vracejí jedna po druhé, aby bylo vidět, jak lišta postupuje.
        $lastReadyAt = $queue === [] ? time() : max(time(), ...array_column($queue, 'readyAt'));
        $id = 'sim-' . Random::generate(12);
        $queue[$id] = [
            'id' => $id,
            'languageId' => $languageId,
            'outputLocale' => $outputLocale,
            'value' => $value,
            'readyAt' => $lastReadyAt + $this->delay,
        ];
        $cache->save(self::CACHE_KEY, $queue, [Cache::Expire => '1 day']);

        return $id;
    }

    /**
     * Vrátí dávky, jejichž čas už nastal, a předá je `$callback` ve tvaru skutečného
     * DropCore callbacku. Vrací počet zpracovaných dávek.
     *
     * @param callable(int $languageId, array{id: string, value: array<string, mixed>}): void $callback
     */
    public function processDue(callable $callback): int
    {
        $cache = $this->getCache();
        $queue = $this->loadQueue($cache);

        $due = array_filter($queue, static fn(array $item): bool => $item['readyAt'] <= time());
        if ($due === []) {
            return 0;
        }

        // Z fronty je odebereme hned, aby je souběžný polling nezpracoval dvakrát.
        $cache->save(self::CACHE_KEY, array_diff_key($queue, $due), [Cache::Expire => '1 day']);

        foreach ($due as $item) {
            $prefix = '[' . strtoupper($item['outputLocale']) . '] ';
            // Každá hodnota dávky (klíč systemName:id:pole) je text k překladu; uvnitř
            // JSON struktury pak rozhodují klíče TEXT_KEYS.
            $value = [];
            foreach ($item['value'] as $key => $text) {
                $value[$key] = $this->fakeTranslate($text, $prefix, true);
            }
            $callback($item['languageId'], ['id' => $item['id'], 'value' => $value]);
        }

        return count($due);
    }

    /**
     * @param bool $translatable je hodnota text k překladu (nejvyšší úroveň dávky ano,
     *                           uvnitř JSON struktury jen pod klíči TEXT_KEYS)
     */
    private function fakeTranslate(mixed $value, string $prefix, bool $translatable): mixed
    {
        if (is_string($value)) {
            return $translatable && trim($value) !== '' ? $prefix . $value : $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        $result = [];
        foreach ($value as $key => $item) {
            // Seznam (např. položky listu) dědí překládání od rodiče, u objektu rozhoduje klíč.
            $itemTranslatable = is_int($key) ? $translatable : in_array($key, self::TEXT_KEYS, true);
            $result[$key] = $this->fakeTranslate($item, $prefix, $itemTranslatable);
        }

        return $result;
    }

    /**
     * @return array<string, array{id: string, languageId: int, outputLocale: string, value: array<string, mixed>, readyAt: int}>
     */
    private function loadQueue(Cache $cache): array
    {
        $queue = $cache->load(self::CACHE_KEY);

        return is_array($queue) ? $queue : [];
    }

    private function getCache(): Cache
    {
        return new Cache($this->storage, self::CACHE_NAMESPACE);
    }
}
