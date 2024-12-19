<?php

namespace App\Model;

use Nette\Database\Explorer;
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
}