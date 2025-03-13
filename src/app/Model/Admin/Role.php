<?php

namespace App\Model\Admin;

use App\Model\Entity\RoleEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Role implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<RoleEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('role');
    }

    /**
     * @return null|RoleEntity
     */
    public function findBySystemName(string $systemName): ?ActiveRow
    {
        return $this->getTable()->where('system_name', $systemName)->fetch();
    }

    /**
     * @return Selection<RoleEntity>
     */
    public function getToGrid(): Selection
    {
        return $this->getTable();
    }

    /**
     * @return ?RoleEntity
     */
    public function getBySystemName(string $systemName): ?ActiveRow
    {
        return $this->getTable()->where('system_name', $systemName)->fetch();
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    public function insert(array $data): void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @return ?RoleEntity
     */
    public function get(int $id): ?ActiveRow
    {
        return $this->getTable()->get($id);
    }

    /**
     * @return Selection<RoleEntity>
     */
    public function getToSelect():Selection
    {
        return $this->getTable();
    }
}
