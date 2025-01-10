<?php

namespace App\Model;

use App\Model\Entity\ModuleEntity;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class Module implements Model
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @return Selection<\App\Model\Entity\ModuleEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('module');
    }

    /**
     * @return Selection<\App\Model\Entity\ModuleEntity>
     */
    public function getToMenu():Selection
    {
        return $this->getTable();
    }

    /**
     * @param string $systemName
     * @return ?ModuleEntity
     */
    public function getBySystemName(string $systemName): ?ActiveRow
    {
        return $this->getTable()->where('system_name', $systemName)->fetch();
    }
}