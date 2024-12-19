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
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $subject;

    #[ORM\Column()]
    private \DateTimeImmutable $created;

    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\Column(type: 'text', length: 255)]
    private string $address;

    #[ORM\Column(type: 'text', length: 255, nullable: true, options: ['default' => null])]
    private ?string $error = null;
}