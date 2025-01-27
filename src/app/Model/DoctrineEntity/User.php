<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $firstname;

    #[ORM\Column(type: 'string', length: 255)]
    private string $lastname;

    #[ORM\Column(type: 'string', length: 255)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Role $role;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['default' => null])]
    private ?\DateTimeImmutable $last_login = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $forgot_password_hash = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['default' => null])]
    private ?\DateTimeImmutable $forgot_password_expire = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $avatar = null;
}
