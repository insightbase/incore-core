<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

/**
 * @property int      $id
 * @property string   $subject
 * @property DateTime $created
 * @property string   $text
 * @property string   $address
 * @property ?string  $error
 */
class EmailLogEntity extends ActiveRow {}
