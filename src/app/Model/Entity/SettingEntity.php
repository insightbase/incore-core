<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property-read int $id
 * @property-read string $email
 * @property-read ?string $email_sender
 * @property-read ?string $recaptcha_secret_key
 * @property-read ?string $recaptcha_site_key
 * @property-read ?string $favicon
 * @property-read ?string $shareimage
 * @property-read ?string $smtp_host
 * @property-read ?string $smtp_username
 * @property-read ?string $smtp_password
 * @property-read ?string $logo
 * @property-read ?string $logo_small
 * @property-read ?string $logo_dark
 * @property-read ?string $logo_dark_small
 */
class SettingEntity extends ActiveRow
{
}
