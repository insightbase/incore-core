<?php

namespace App\Model;

use App\Model\Entity\LanguageEntity;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Language implements Model
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @return Selection<LanguageEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('language');
    }

    /**
     * @return Selection<LanguageEntity>
     */
    public function getToGrid():Selection
    {
        return $this->getTable();
    }

    public function insert(array $data):void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @param int $id
     * @return ?LanguageEntity
     */
    public function get(int $id):?ActiveRow
    {
        return $this->getTable()->get($id);
    }

    /**
     * @return Selection<LanguageEntity>
     */
    public function getToTranslate():Selection
    {
        return $this->getTable();
    }
}