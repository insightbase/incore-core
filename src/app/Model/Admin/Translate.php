<?php

namespace App\Model\Admin;

use App\Model\Entity\TranslateEntity;
use App\Model\Model;
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
    public function getToGrid(string $source): Selection
    {
        return $this->getTable()->where('source', $source);
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
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
    public function getNotKeys(array $keys, string $source): Selection
    {
        if(empty($keys)){
            return $this->getTable()
                ->where('source', $source);
        }else {
            return $this->getTable()
                ->where('NOT key', $keys)
                ->where('source', $source);
        }
    }

    /**
     * @return Selection<TranslateEntity>
     */
    public function getAll():Selection
    {
        return $this->getTable();
    }

    /**
     * @return Selection<TranslateEntity>
     */
    public function getToGridPerformance():Selection
    {
        return $this->getTable()->where('is_performance', true);
    }

    /**
     * @return Selection<TranslateEntity>
     */
    public function getNotAdmin():Selection
    {
        return $this->getTable()->where('NOT source', 'admin');
    }
}
