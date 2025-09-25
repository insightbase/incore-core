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