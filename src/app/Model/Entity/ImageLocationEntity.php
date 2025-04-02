<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $location
 * @property-read bool $show_in_grid
 */
class ImageLocationEntity extends ActiveRow
{
}
