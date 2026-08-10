<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read ImageEntity $image
 * @property-read int $image_id
 * @property-read LanguageEntity $language
 * @property-read int $language_id
 * @property-read ?string $alt
 * @property-read ?string $name
 * @property-read ?string $description
 */
class ImageLanguageEntity extends ActiveRow
{
}
