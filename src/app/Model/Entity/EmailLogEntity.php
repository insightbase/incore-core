<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

/**
 * @property-read int $id
 * @property-read string $subject
 * @property-read DateTime $created
 * @property-read string $text
 * @property-read string $address
 * @property-read ?string $error
 * @property-read ?string $from
 */
class EmailLogEntity extends ActiveRow
{
}
