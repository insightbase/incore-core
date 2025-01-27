<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Nette\Database\Table\ActiveRow;

/**
 * @property int     $id
 * @property string  $email
 * @property ?string $email_sender
 * @property ?string $recaptcha_secret_key
 * @property ?string $recaptcha_site_key
 * @property ?string $favicon
 * @property ?string $shareimage
 * @property ?string $smtp_host
 * @property ?string $smtp_username
 * @property ?string $smtp_password
 */
class SettingEntity extends ActiveRow {}
