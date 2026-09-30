<?php

namespace App\Component\Translation;

use App\Model\Admin\LanguageTranslate;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Security\User;
use Nette\Utils\Random;

/**
 * Rozpracované překlady přihlášeného uživatele, které admin zobrazuje v liště s průběhem.
 * Úloha je seznam dávek v `language_translate`. Lišta je po jedné odesílá do DropCore
 * (sendNext() vyplní `drop_core_id`); hotová je, když všechny mají vyplněný `finished`
 * (nastaví ho callback DropCore).
 */
final readonly class TranslationJobFacade
{
    private const string CACHE_NAMESPACE = 'translationJob';

    public function __construct(
        private Storage $storage,
        private User $userSecurity,
        private LanguageTranslate $languageTranslateModel,
        private TranslationBatchSender $translationBatchSender,
    ) {}

    /**
     * @param list<int> $translateIds dávky vrácené z LanguageFacade (translate*, translateProviderItem(s))
     * @param int $itemCount počet přeložených položek pro souhrn v liště; 0 = souhrn se nezobrazí
     */
    public function start(string $label, array $translateIds, int $itemCount = 0): void
    {
        if ($translateIds === []) {
            return;
        }

        $jobs = $this->load();
        $id = Random::generate(10);
        $jobs[$id] = [
            'id' => $id,
            'label' => $label,
            'translateIds' => $translateIds,
            'itemCount' => $itemCount,
        ];
        $this->save($jobs);
    }

    /**
     * @return list<array{id: string, label: string, itemCount: int, total: int, sent: int, finished: int, error: ?string, done: bool}>
     */
    public function getStatuses(): array
    {
        $statuses = [];
        foreach ($this->load() as $job) {
            $progress = $this->languageTranslateModel->getProgress($job['translateIds']);
            $statuses[] = [
                'id' => $job['id'],
                'label' => $job['label'],
                'itemCount' => $job['itemCount'],
                'total' => $progress['total'],
                'sent' => $progress['sent'],
                'finished' => min($progress['finished'], $progress['total']),
                'error' => $progress['error'],
                'done' => $progress['finished'] >= $progress['total'],
            ];
        }

        return $statuses;
    }

    /**
     * Odešle další dávku úlohy do DropCore. Vrací true, když nějakou dávku zpracoval.
     */
    public function sendNext(string $id): bool
    {
        $job = $this->load()[$id] ?? null;

        return $job !== null && $this->translationBatchSender->sendNext($job['translateIds']);
    }

    /**
     * „Zkusit znovu“ po chybě odeslání - dávky s chybou se znovu zařadí k odeslání.
     */
    public function retry(string $id): void
    {
        $job = $this->load()[$id] ?? null;
        if ($job !== null) {
            $this->languageTranslateModel->clearErrors($job['translateIds']);
        }
    }

    /**
     * „Zrušit“ - smaže neodeslané dávky a úlohu z lišty. Odeslané dávky doběhnou.
     */
    public function cancel(string $id): void
    {
        $job = $this->load()[$id] ?? null;
        if ($job !== null) {
            $this->languageTranslateModel->deleteUnsent($job['translateIds'], new \DateTimeImmutable());
        }
        $this->dismiss($id);
    }

    public function dismiss(string $id): void
    {
        $jobs = $this->load();
        unset($jobs[$id]);
        $this->save($jobs);
    }

    /**
     * @return array<string, array{id: string, label: string, translateIds: list<int>, itemCount: int}>
     */
    private function load(): array
    {
        $key = $this->getKey();
        if ($key === null) {
            return [];
        }

        $jobs = (new Cache($this->storage, self::CACHE_NAMESPACE))->load($key);
        if (!is_array($jobs)) {
            return [];
        }

        // Úlohy z doby před asynchronním odesíláním (klíč dropCoreIds) lišta už neumí zobrazit.
        return array_filter($jobs, static fn(mixed $job): bool => isset($job['translateIds']));
    }

    /**
     * @param array<string, array{id: string, label: string, translateIds: list<int>, itemCount: int}> $jobs
     */
    private function save(array $jobs): void
    {
        $key = $this->getKey();
        if ($key === null) {
            return;
        }

        $cache = new Cache($this->storage, self::CACHE_NAMESPACE);
        if ($jobs === []) {
            $cache->remove($key);

            return;
        }

        // Nedoběhlá úloha (DropCore callback nikdy nepřišel) nesmí lištu blokovat navždy.
        $cache->save($key, $jobs, [Cache::Expire => '1 day']);
    }

    private function getKey(): ?string
    {
        $userId = $this->userSecurity->isLoggedIn() ? $this->userSecurity->getId() : null;

        return $userId === null ? null : 'user_' . $userId;
    }
}
