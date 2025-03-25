<?php

namespace App\Model\DoctrineEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'file')]
class File implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string', length: 255)]
    public string $original_name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    public string $saved_name;
}