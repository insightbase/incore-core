<?php

namespace App\Model\DoctrineEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'log')]
class Log implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    public User $user;

    #[ORM\Column(type: 'string', length: 255)]
    public string $action;

    #[ORM\Column()]
    public \DateTimeImmutable $created;

    #[ORM\Column(type: 'string', length: 255)]
    public string $table;

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $target_id = null;
}