<?php

namespace App\Model;

use App\Model\Entity\TranslateEntity;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Translate implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<TranslateEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('translate');
    }

    /**
     * @return Selection<TranslateEntity>
     */
    public function getToGrid(): Selection
    {
        return $this->getTable();
    }

    public function insert(array $data): void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @return ?TranslateEntity
     */
    public function get(int $id): ?ActiveRow
    {
        return $this->getTable()->get($id);
    }

    /**
     * @return ?TranslateEntity
     */
    public function getByKey(string $key): ?ActiveRow
    {
        return $this->getTable()->where('key', $key)->fetch();
    }

    /**
     * @return Selection<TranslateEntity>
     */
    public function getNotKeys(array $keys): Selection
    {
        return $this->getTable()->where('NOT key', $keys);
    }

    /**
     * @return Selection<TranslateEntity>
     */
    public function getAll():Selection
    {
        return $this->getTable();
    }
}
