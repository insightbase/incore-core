<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $system_name
 * @property-read string $name
 * @property-read ?string $presenter
 * @property-read ?string $icon
 * @property-read ?ModuleEntity $parent
 * @property-read ?int $parent_id
 * @property-read string $position
 * @property-read string $action
 * @property-read bool $active
 */
class ModuleEntity extends ActiveRow
{
}
