<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'role')]
class Role implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $system_name;

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setSystemName(string $system_name): self
    {
        $this->system_name = $system_name;
        return $this;
    }
}