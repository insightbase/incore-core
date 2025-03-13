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
    public int $id;

    #[ORM\Column(type: 'string', length: 255)]
    public string $firstname;

    #[ORM\Column(type: 'string', length: 255)]
    public string $lastname;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    public string $email;

    #[ORM\Column(type: 'string', length: 255)]
    public string $password;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(nullable: false)]
    public Role $role;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['default' => null])]
    public ?\DateTimeImmutable $last_login = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    public ?string $forgot_password_hash = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['default' => null])]
    public ?\DateTimeImmutable $forgot_password_expire = null;

    #[ORM\ManyToOne(targetEntity: Image::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public ?Image $avatar = null;
}
