<?php

namespace App\Model\Admin;

use App\Component\Translation\PendingTranslateBatch;
use App\Model\Entity\LanguageTranslateEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class LanguageTranslate implements Model
{
    /** Odeslání dávky do DropCore selhalo pro nedostatek kreditů (HTTP 402). */
    public const string ERROR_NOT_ENOUGH_CREDITS = 'notEnoughCredits';

    /** Odeslání dávky do DropCore selhalo z jiného důvodu. */
    public const string ERROR_API = 'api';

    /** Zámek dávky, jejíž odesílání spadlo uprostřed (fatal, timeout), po této době vyprší. */
    private const string LOCK_TIMEOUT = '-2 minutes';

    public function __construct(
        private Explorer $explorer,
    ) {
    }

    /**
     * @return Selection<LanguageTranslateEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('language_translate');
    }

    public function insert(array $data):void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @return Selection<LanguageTranslateEntity>
     */
    public function getToGrid(): Selection
    {
        return $this->getTable()->order('datetime DESC');
    }

    /**
     * Označí dosud nedokončený záznam daného DropCore ID jako dokončený. Vrací počet označených.
     */
    public function markFinishedByDropCoreId(string $dropCoreId, \DateTime $datetime): int
    {
        return $this->getTable()
            ->where('drop_core_id', $dropCoreId)
            ->where('finished', null)
            ->update(['finished' => $datetime]);
    }

    /**
     * @param string $id
     * @return ?LanguageTranslateEntity
     */
    public function getByDropCoreId(string $id):?ActiveRow
    {
        return $this->getTable()
            ->where('drop_core_id', $id)
            ->fetch();
    }

    /**
     * Uloží dávku k pozdějšímu odeslání do DropCore (drop_core_id zatím není).
     */
    public function insertPending(int $userId, int $languageId, string $request): int
    {
        $row = $this->getTable()->insert([
            'user_id' => $userId,
            'language_id' => $languageId,
            'datetime' => new \DateTime(),
            'request' => $request,
        ]);
        if (!$row instanceof ActiveRow) {
            throw new \LogicException('Dávku překladu se nepodařilo uložit do language_translate.');
        }

        return (int) $row->id;
    }

    /**
     * Má některá dávka chybu odeslání? Taková úloha se neodesílá, dokud uživatel nedá „Zkusit znovu“.
     *
     * @param list<int> $ids
     */
    public function hasError(array $ids): bool
    {
        return $ids !== [] && $this->getTable()
            ->where('id', $ids)
            ->where('error IS NOT NULL')
            ->count('*') > 0;
    }

    /**
     * Zamkne první neodeslanou dávku z `$ids` (podle id) a vrátí ji. Zamyká se atomickým
     * UPDATE, takže dvě souběžná odesílání (dvě záložky) si stejnou dávku nevezmou.
     *
     * @param list<int> $ids
     */
    public function claimNext(array $ids, \DateTimeImmutable $now): ?PendingTranslateBatch
    {
        if ($ids === []) {
            return null;
        }

        $lockExpired = $now->modify(self::LOCK_TIMEOUT);
        foreach ($this->getSendable($ids, $lockExpired)->order('id')->fetchAll() as $row) {
            $claimed = $this->getSendable([$row->id], $lockExpired)->update(['sending_started' => $now]);
            if ($claimed === 1) {
                return new PendingTranslateBatch($row->id, $row->language_id, $row->request);
            }
        }

        return null;
    }

    /**
     * @param ?\DateTimeInterface $finished callback DropCore už dorazil (předběhl uložení drop_core_id)
     */
    public function markSent(int $id, string $dropCoreId, ?\DateTimeInterface $finished): void
    {
        $this->getTable()->where('id', $id)->update([
            'drop_core_id' => $dropCoreId,
            'finished' => $finished,
            'sending_started' => null,
        ]);
    }

    public function markError(int $id, string $error): void
    {
        $this->getTable()->where('id', $id)->update([
            'error' => $error,
            'sending_started' => null,
        ]);
    }

    /**
     * @param list<int> $ids
     */
    public function clearErrors(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $this->getTable()
            ->where('id', $ids)
            ->where('drop_core_id', null)
            ->update(['error' => null]);
    }

    /**
     * Smaže neodeslané dávky. Dávku, kterou právě odesílá jiný request (platný zámek),
     * nechá být - doběhne a její překlad se normálně uloží.
     *
     * @param list<int> $ids
     */
    public function deleteUnsent(array $ids, \DateTimeImmutable $now): void
    {
        if ($ids === []) {
            return;
        }

        $this->getTable()
            ->where('id', $ids)
            ->where('drop_core_id', null)
            ->where('(sending_started IS NULL OR sending_started < ?)', $now->modify(self::LOCK_TIMEOUT))
            ->delete();
    }

    /**
     * Průběh úlohy pro lištu. `total` počítá jen existující řádky - po smazání jazyka
     * (ON DELETE CASCADE) úloha nezůstane viset jako nedokončená.
     *
     * @param list<int> $ids
     * @return array{total: int, sent: int, finished: int, error: ?string}
     */
    public function getProgress(array $ids): array
    {
        if ($ids === []) {
            return ['total' => 0, 'sent' => 0, 'finished' => 0, 'error' => null];
        }

        $errorRow = $this->getTable()->where('id', $ids)->where('error IS NOT NULL')->order('id')->fetch();

        return [
            'total' => $this->getTable()->where('id', $ids)->count('*'),
            'sent' => $this->getTable()->where('id', $ids)->where('drop_core_id IS NOT NULL')->count('*'),
            'finished' => $this->getTable()->where('id', $ids)->where('finished IS NOT NULL')->count('*'),
            'error' => $errorRow?->error,
        ];
    }

    /**
     * @param list<int> $ids
     * @return Selection<LanguageTranslateEntity>
     */
    private function getSendable(array $ids, \DateTimeImmutable $lockExpired): Selection
    {
        return $this->getTable()
            ->where('id', $ids)
            ->where('drop_core_id', null)
            ->where('error', null)
            ->where('(sending_started IS NULL OR sending_started < ?)', $lockExpired);
    }
}