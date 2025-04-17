<?php

namespace App\Model\Admin;

use App\Model\Entity\EmailLogEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class EmailLog implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<EmailLogEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('email_log');
    }

    /**
     * @return Selection<EmailLogEntity>
     */
    public function getToGrid():Selection
    {
        return $this->getTable();
    }

    /**
     * @param int $id
     * @return ?EmailLogEntity
     */
    public function get(int $id):?ActiveRow
    {
        return $this->getTable()->get($id);
    }
}
