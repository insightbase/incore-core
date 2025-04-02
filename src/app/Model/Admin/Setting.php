<?php

namespace App\Model\Admin;

use App\Model\Entity\SettingEntity;
use App\Model\Model;
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

    /**
     * @param array<string, mixed> $data
     * @return SettingEntity
     */
    public function insert(array $data): ActiveRow
    {
        return $this->getTable()->insert($data);
    }
}
