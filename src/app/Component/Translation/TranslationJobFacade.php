<?php

namespace App\Component\Translation;

use App\Model\Admin\LanguageTranslate;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Security\User;
use Nette\Utils\Random;

/**
 * Rozpracované překlady přihlášeného uživatele, které admin zobrazuje v liště s průběhem.
 * Úloha je seznam dávek odeslaných do DropCore; hotová je, když všechny její dávky
 * mají v `language_translate` vyplněný `finished` (nastaví ho callback DropCore).
 */
final readonly class TranslationJobFacade
{
    private const string CACHE_NAMESPACE = 'translationJob';

    public function __construct(
        private Storage $storage,
        private User $userSecurity,
        private LanguageTranslate $languageTranslateModel,
    ) {}

    /**
     * @param list<string> $dropCoreIds dávky vrácené z LanguageFacade (translate*, translateProviderItem(s))
     * @param int $itemCount počet přeložených položek pro souhrn v liště; 0 = souhrn se nezobrazí
     */
    public function start(string $label, array $dropCoreIds, int $itemCount = 0): void
    {
        if ($dropCoreIds === []) {
            return;
        }

        $jobs = $this->load();
        $id = Random::generate(10);
        $jobs[$id] = [
            'id' => $id,
            'label' => $label,
            'dropCoreIds' => $dropCoreIds,
            'itemCount' => $itemCount,
        ];
        $this->save($jobs);
    }

    /**
     * @return list<array{id: string, label: string, itemCount: int, total: int, finished: int, done: bool}>
     */
    public function getStatuses(): array
    {
        $statuses = [];
        foreach ($this->load() as $job) {
            $total = count($job['dropCoreIds']);
            $finished = $this->languageTranslateModel->getTable()
                ->where('drop_core_id', $job['dropCoreIds'])
                ->where('finished IS NOT NULL')
                ->count('*');
            $statuses[] = [
                'id' => $job['id'],
                'label' => $job['label'],
                'itemCount' => $job['itemCount'],
                'total' => $total,
                'finished' => min($finished, $total),
                'done' => $finished >= $total,
            ];
        }

        return $statuses;
    }

    public function dismiss(string $id): void
    {
        $jobs = $this->load();
        unset($jobs[$id]);
        $this->save($jobs);
    }

    /**
     * @return array<string, array{id: string, label: string, dropCoreIds: list<string>, itemCount: int}>
     */
    private function load(): array
    {
        $key = $this->getKey();
        if ($key === null) {
            return [];
        }

        $jobs = (new Cache($this->storage, self::CACHE_NAMESPACE))->load($key);

        return is_array($jobs) ? $jobs : [];
    }

    /**
     * @param array<string, array{id: string, label: string, dropCoreIds: list<string>, itemCount: int}> $jobs
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
