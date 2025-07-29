<?php

namespace App\UI\Admin\Setting\Form;

class EditFormData
{
    public ?int $logo_id;
    public ?int $logo_dark_id;
    public ?int $logo_small_id;
    public ?int $logo_dark_small_id;
    public ?int $shareimage_id;
    public ?string $email;
    public ?string $email_sender;
    public ?string $smtp_host;
    public ?string $smtp_username;
    public ?string $smtp_password;
    public ?string $recaptcha_secret_key;
    public ?string $recaptcha_site_key;
    public ?int $max_image_resolution;
    public ?int $google_service_account_id;
    public ?string $basic_auth_password;
    public ?string $basic_auth_user;
}
