<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'favicon')]

class Favicon implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\ManyToOne(targetEntity: Image::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public ?Image $image = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $rel = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $type = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $sizes = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $href = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $name = null;

    #[ORM\Column(type: 'string', length: 255)]
    public ?string $tag = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $content = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $image_to_attribute = null;

}