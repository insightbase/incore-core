<?php

namespace App\Model;

use App\Model\Entity\PermissionEntity;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection;

readonly class Permission implements Model
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @return Selection<PermissionEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('permission');
    }
}