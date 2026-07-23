<?php

namespace App\Model\Admin;

use App\Model\Entity\LanguageTranslateEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class LanguageTranslate implements Model
{
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
}