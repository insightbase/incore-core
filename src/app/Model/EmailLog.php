<?php

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\Selection;

class EmailLog
{
    /**
     * @return Selection<\App\Model\Entity\EmailLogEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('email_log');
    }

    public function __construct(
        private readonly Explorer $explorer,
    )
    {
    }
}