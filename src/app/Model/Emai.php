<?php

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Email
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @return ?\App\Model\Entity\EmailEntity
     */
    public function getBySystemName(string $systemName):?ActiveRow
    {
        return $this->getTable()->where('system_name', $systemName)->fetch();
    }

    /**
     * @return Selection<\App\Model\Entity\EmailEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('email');
    }
}