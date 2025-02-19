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
 * @property-read ?ImageEntity $favicon
 * @property-read ?int $favicon_id
 * @property-read ?ImageEntity $shareimage
 * @property-read ?int $shareimage_id
 * @property-read ?string $smtp_host
 * @property-read ?string $smtp_username
 * @property-read ?string $smtp_password
 * @property-read ?ImageEntity $logo
 * @property-read ?int $logo_id
 * @property-read ?ImageEntity $logo_small
 * @property-read ?int $logo_small_id
 * @property-read ?ImageEntity $logo_dark
 * @property-read ?int $logo_dark_id
 * @property-read ?ImageEntity $logo_dark_small
 * @property-read ?int $logo_dark_small_id
 * @property-read ?int $max_image_resolution
 */
class SettingEntity extends ActiveRow
{
}
