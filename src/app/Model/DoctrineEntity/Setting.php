<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'setting')]
class Setting implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => 'info@email.cz'])]
    private string $email;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $email_sender = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $recaptcha_secret_key = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $recaptcha_site_key = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $favicon = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $shareimage = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $smtp_host = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $smtp_username = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $smtp_password = null;
}
