<?php

namespace App\Model;

use App\Model\Entity\UserEntity;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class User
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @param int $id
     * @return UserEntity|null
     */
    public function get(int $id):?ActiveRow
    {
        return $this->getTable()->get($id);
    }

    /**
     * @return Selection<\App\Model\Entity\UserEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('user');
    }

    /**
     * @param string $email
     * @return UserEntity|null
     */
    public function findByEmail(string $email):?ActiveRow
    {
        return $this->getTable()
            ->where('email', $email)
            ->fetch();
    }

    public function insert(array $data):void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @param string $hash
     * @return ?UserEntity
     */
    public function findByHash(string $hash):?ActiveRow
    {
        return $this->getTable()
            ->where('forgot_password_hash', $hash)
            ->fetch();
    }
}