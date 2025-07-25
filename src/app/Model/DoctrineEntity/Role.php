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
    public int $id;

    #[ORM\Column(type: 'string', length: 255)]
    public string $name;

    #[ORM\Column(type: 'string', length: 255)]
    public string $system_name;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    public bool $is_systemic = false;

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

    public function setIsSystemic(bool $is_systemic): self
    {
        $this->is_systemic = $is_systemic;

        return $this;
    }
}
