<?php

namespace App\Model\Admin;

use App\Model\Entity\EmailLogEntity;
use App\Model\Model;
use Nette\Database\Explorer;
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
}
