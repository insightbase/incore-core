<?php

namespace App\Model\DoctrineEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'module')]
class Module implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $system_name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $presenter;

    #[ORM\Column(type: 'string', length: 255)]
    private string $icon;

    public function setSystemName(string $systemName):self{
        $this->system_name = $systemName;
        return $this;
    }

    public function setName(string $name):self{
        $this->name = $name;
        return $this;
    }

    public function setPresenter(string $presenter):self{
        $this->presenter = $presenter;
        return $this;
    }

    public function setIcon(string $icon):self{
        $this->icon = $icon;
        return $this;
    }
}