<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

/**
 * @property-read int $id
 * @property-read string $firstname
 * @property-read string $lastname
 * @property-read string $email
 * @property-read string $password
 * @property-read RoleEntity $role
 * @property-read ?DateTime $last_login
 * @property-read ?string $forgot_password_hash
 * @property-read ?DateTime $forgot_password_expire
 * @property-read ?string $avatar
 */
class UserEntity extends ActiveRow
{
}
