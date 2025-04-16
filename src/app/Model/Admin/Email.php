<?php

namespace App\Model\Admin;

use App\Model\Entity\EmailEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Email implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return ?EmailEntity
     */
    public function getBySystemName(string $systemName): ?ActiveRow
    {
        return $this->getTable()->where('system_name', $systemName)->fetch();
    }

    /**
     * @return Selection<EmailEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('email');
    }

    /**
     * @param int $id
     * @return ?EmailEntity
     */
    public function get(int $id):?ActiveRow
    {
        return $this->getTable()->get($id);
    }

    /**
     * @param array $data
     * @return ?EmailEntity
     */
    public function insert(array $data):?ActiveRow
    {
        return $this->getTable()->insert($data);
    }

    /**
     * @return Selection<EmailEntity>
     */
    public function getToGrid():Selection
    {
        return $this->getTable();
    }
}
