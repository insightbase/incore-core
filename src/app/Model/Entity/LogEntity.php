<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

/**
 * @property-read int $id
 * @property-read UserEntity $user
 * @property-read int $user_id
 * @property-read string $action
 * @property-read DateTime $created
 * @property-read string $table
 * @property-read ?int $target_id
 */
class LogEntity extends ActiveRow
{
}
