<?php

namespace App\Model\DoctrineEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'static_page')]
class StaticPage implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string', length: 255)]
    public string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    public string $slug;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    public string $system_name;

    #[ORM\Column(type: 'string', length: 255)]
    public string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $keywords = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $content = null;

    #[ORM\Column(type: 'boolean')]
    public bool $active;
}