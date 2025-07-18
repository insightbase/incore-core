<?php

namespace App\Model\Admin;

use App\Model\Entity\UserEntity;
use App\Model\Enum\RoleEnum;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class User implements Model
{
    public function __construct(
        private Explorer $explorer,
        private \Nette\Security\User $userSecurity,
    ) {}

    /**
     * @return null|UserEntity
     */
    public function get(int $id): ?ActiveRow
    {
        return $this->getTable()->get($id);
    }

    /**
     * @return Selection<UserEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('user');
    }

    /**
     * @return null|UserEntity
     */
    public function findByEmail(string $email, ?int $ignoreId = null): ?ActiveRow
    {
        $selection = $this->getTable()
            ->where('email', $email)
        ;
        if (null !== $ignoreId) {
            $selection->where('id <> ?', $ignoreId);
        }

        return $selection->fetch();
    }

    /**
     * @param array<string, mixed> $data
     * @return UserEntity
     */
    public function insert(array $data): ActiveRow
    {
        return $this->getTable()->insert($data);
    }

    /**
     * @return ?UserEntity
     */
    public function findByHash(string $hash): ?ActiveRow
    {
        return $this->getTable()
            ->where('forgot_password_hash', $hash)
            ->fetch()
        ;
    }

    public function getToGrid():Selection
    {
        if($this->userSecurity->isInRole(RoleEnum::SUPER_ADMIN->value)){
            return $this->getTable();
        }else{
            return $this->getTable()->where('role.system_name NOT IN ?', [RoleEnum::SUPER_ADMIN->value]);
        }
    }
}
