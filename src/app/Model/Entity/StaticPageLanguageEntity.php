<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read LanguageEntity $language
 * @property-read int $language_id
 * @property-read StaticPageEntity $static_page
 * @property-read int $static_page_id
 * @property-read ?string $name
 * @property-read ?string $slug
 * @property-read ?string $title
 * @property-read ?string $description
 * @property-read ?string $keywords
 * @property-read ?string $content
 */
class StaticPageLanguageEntity extends ActiveRow
{
}
