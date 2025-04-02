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
    public int $id;

    #[ORM\Column(type: 'string', length: 255)]
    public string $original_name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    public string $saved_name;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    public ?string $alt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    public ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    public ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    public ?string $author = null;

    #[ORM\ManyToOne(targetEntity: ImageLocation::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public ?ImageLocation $image_location = null;
}