<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property int             $id
 * @property string          $value
 * @property LanguageEntity  $language
 * @property TranslateEntity $translate
 */
class TranslateLanguageEntity extends ActiveRow {}
