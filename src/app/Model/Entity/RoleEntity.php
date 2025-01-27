<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property int    $id
 * @property string $name
 * @property string $system_name
 * @property bool   $is_systemic
 */
class RoleEntity extends ActiveRow {}
