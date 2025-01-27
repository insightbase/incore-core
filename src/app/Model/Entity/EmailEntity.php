<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property int     $id
 * @property string  $system_name
 * @property string  $name
 * @property string  $text
 * @property string  $subject
 * @property ?string $modifier
 */
class EmailEntity extends ActiveRow {}
