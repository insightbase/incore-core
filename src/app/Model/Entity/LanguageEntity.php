<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property int     $id
 * @property string  $name
 * @property string  $locale
 * @property ?string $flag
 * @property bool    $is_default
 * @property string  $url
 * @property bool    $active
 */
class LanguageEntity extends ActiveRow {}
