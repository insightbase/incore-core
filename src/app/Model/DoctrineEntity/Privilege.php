<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'privilege')]
class Privilege implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    public string $system_name;

    #[ORM\Column(type: 'string', length: 255)]
    public string $name;

    public function setSystemName(string $system_name): self
    {
        $this->system_name = $system_name;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
