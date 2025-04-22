<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'email_log')]
class EmailLog implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string', length: 255)]
    public string $subject;

    #[ORM\Column()]
    public \DateTimeImmutable $created;

    #[ORM\Column(type: 'text')]
    public string $text;

    #[ORM\Column(type: 'text', length: 255)]
    public string $address;

    #[ORM\Column(type: 'text', length: 255, nullable: true, options: ['default' => null])]
    public ?string $error = null;

    #[ORM\Column(type: 'text', length: 255, nullable: true, options: ['default' => null])]
    public ?string $from = null;
}
