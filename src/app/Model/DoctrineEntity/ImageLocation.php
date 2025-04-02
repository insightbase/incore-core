<?php

namespace App\Model\DoctrineEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'image_location')]
class ImageLocation implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string', length: 255)]
    public string $location;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    public bool $show_in_grid = false;
}