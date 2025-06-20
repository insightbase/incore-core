<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $original_name
 * @property-read string $saved_name
 * @property-read ?string $alt
 * @property-read ?string $name
 * @property-read ?string $description
 * @property-read ?string $author
 * @property-read string $type
 * @property-read ?ImageLocationEntity $image_location
 * @property-read ?int $image_location_id
 */
class ImageEntity extends ActiveRow
{
}
