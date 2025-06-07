<?php

namespace App\Model\Admin;

use App\Model\Entity\LogEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection;

readonly class Log implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {
    }

    /**
     * @return Selection<LogEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('log');
    }

    public function insert(array $data): void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @return Selection<LogEntity>
     */
    public function getToGrid(): Selection
    {
        return $this->getTable();
    }

    /**
     * Získání posledních aktivit pro timeline
     * @return Selection<LogEntity>
     */
    public function getRecent(int $limit = 30): Selection
    {
        return $this->getTable()
            ->order('created DESC')
            ->limit($limit);
    }
}
