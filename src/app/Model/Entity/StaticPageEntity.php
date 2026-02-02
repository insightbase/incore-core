<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $slug
 * @property-read string $system_name
 * @property-read string $title
 * @property-read ?string $description
 * @property-read ?string $keywords
 * @property-read ?string $content
 * @property-read bool $active
 */
class StaticPageEntity extends ActiveRow
{
}
