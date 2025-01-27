<?php

namespace App\Model;

use App\Model\Entity\SettingEntity;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Setting implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<SettingEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('setting');
    }

    /**
     * @return ?SettingEntity
     */
    public function getDefault(): ?ActiveRow
    {
        return $this->getTable()->fetch();
    }

    public function insert(array $data): void
    {
        $this->getTable()->insert($data);
    }
}
