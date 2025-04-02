<?php

namespace App\Model\Admin;

use App\Model\Entity\FaviconEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Favicon implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<FaviconEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('favicon');
    }

    /**
     * @return Selection<FaviconEntity>
     */
    public function getToGrid():Selection
    {
        return $this->getTable();
    }

    /**
     * @param array<string, mixed> $data
     * @return ?FaviconEntity
     */
    public function insert(array $data):?ActiveRow
    {
        return $this->getTable()->insert($data);
    }

    /**
     * @param int $id
     * @return ?FaviconEntity
     */
    public function get(int $id):?ActiveRow
    {
        return $this->getTable()->get($id);
    }
}