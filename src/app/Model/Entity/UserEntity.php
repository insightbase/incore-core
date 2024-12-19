<?php

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $firstname
 * @property-read string $lastname
 * @property-read string $email
 * @property-read string $password
 * @property-read int $role_id
 * @property-read RoleEntity $role
 * @property-read ?string $avatar
 */
class UserEntity extends ActiveRow
{

}