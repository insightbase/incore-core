<?php

namespace App\Model\Admin;

use App\Model\Entity\RoleEntity;
use App\Model\Enum\RoleEnum;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Role implements Model
{
    public function __construct(
        private Explorer $explorer,
        private \Nette\Security\User $userSecurity,
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
        if($this->userSecurity->isInRole(RoleEnum::SUPER_ADMIN->value)){
            return $this->getTable();
        }else{
            return $this->getTable()->where('system_name NOT IN ?', [RoleEnum::SUPER_ADMIN->value]);
        }
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
     * @return RoleEntity
     */
    public function insert(array $data): ActiveRow
    {
        return $this->getTable()->insert($data);
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
        return $this->getToGrid();
    }
}
