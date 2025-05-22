<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $key
 * @property-read string $source
 * @property-read string $type
 * @property-read bool $is_performance
 */
class TranslateEntity extends ActiveRow
{
}
