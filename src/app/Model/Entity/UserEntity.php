<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

/**
 * @property int        $id
 * @property string     $firstname
 * @property string     $lastname
 * @property string     $email
 * @property string     $password
 * @property RoleEntity $role
 * @property ?DateTime  $last_login
 * @property ?string    $forgot_password_hash
 * @property ?DateTime  $forgot_password_expire
 * @property ?string    $avatar
 */
class UserEntity extends ActiveRow {}
