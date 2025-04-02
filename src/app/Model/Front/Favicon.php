<?php

namespace App\Model\Front;

use App\Model\Entity\FaviconEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection;

readonly class Favicon implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<FaviconEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('favicon');
    }

    /**
     * @return Selection<FaviconEntity>
     */
    public function getToFront():Selection
    {
        return $this->getTable();
    }
}