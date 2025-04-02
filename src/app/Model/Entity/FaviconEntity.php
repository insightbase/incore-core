<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read ?ImageEntity $image
 * @property-read ?int $image_id
 * @property-read ?string $rel
 * @property-read ?string $type
 * @property-read ?string $sizes
 * @property-read ?string $href
 * @property-read ?string $name
 * @property-read ?string $tag
 * @property-read ?string $content
 * @property-read ?string $image_to_attribute
 */
class FaviconEntity extends ActiveRow
{
}
