<?php

namespace App\Model;

use App\Model\Entity\RoleEntity;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Role
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @return Selection<\App\Model\Entity\RoleEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('role');
    }

    /**
     * @param string $systemName
     * @return RoleEntity|null
     */
    public function findBySystemName(string $systemName): ?ActiveRow
    {
        return $this->getTable()->where('system_name', $systemName)->fetch();
    }
}