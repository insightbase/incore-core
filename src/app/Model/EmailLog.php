<?php

namespace App\Model;

use App\Model\Entity\EmailLogEntity;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection;

class EmailLog implements Model
{
    public function __construct(
        private readonly Explorer $explorer,
    ) {}

    /**
     * @return Selection<EmailLogEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('email_log');
    }
}
