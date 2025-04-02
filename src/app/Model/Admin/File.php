<?php

namespace App\Model\Admin;

use App\Model\Entity\FileEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class File implements Model
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @return Selection<FileEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('file');
    }

    /**
     * @param int $id
     * @return ?FileEntity
     */
    public function get(int $id):?ActiveRow
    {
        return $this->getTable()->get($id);
    }

    /**
     * @param array<string, mixed> $data
     * @return ?FileEntity
     */
    public function insert(array $data):?ActiveRow
    {
        return $this->getTable()->insert($data);
    }
}