<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $original_name
 * @property-read string $saved_name
 */
class FileEntity extends ActiveRow
{
}
