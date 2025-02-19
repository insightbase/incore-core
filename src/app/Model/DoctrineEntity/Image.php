<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'image')]
class Image implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $original_name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $saved_name;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $alt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $author = null;
}