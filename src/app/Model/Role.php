<?php

namespace App\Model;

use App\Model\Entity\RoleEntity;
use App\Model\Enum\RoleEnum;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Role implements Model
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

    /**
     * @return Selection<RoleEntity>
     */
    public function getToGrid():Selection
    {
        return $this->getTable();
    }

    /**
     * @param string $systemName
     * @return ?RoleEntity
     */
    public function getBySystemName(string $systemName):?ActiveRow
    {
        return $this->getTable()->where('system_name', $systemName)->fetch();
    }

    public function insert(array $data):void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @param int $id
     * @return ?RoleEntity
     */
    public function get(int $id):?ActiveRow
    {
        return $this->getTable()->get($id);
    }
}