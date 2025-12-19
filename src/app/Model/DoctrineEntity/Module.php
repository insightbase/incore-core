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
    public int $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    public string $system_name;

    #[ORM\Column(type: 'string', length: 255)]
    public string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $presenter = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $icon;

    #[ORM\ManyToOne(targetEntity: Module::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    public ?Module $parent = null;

    #[ORM\Column(type: 'integer')]
    public string $position;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => 'default'])]
    public string $action = 'default';

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    public bool $active = true;

    #[ORM\ManyToOne(targetEntity: Enumeration::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public ?Enumeration $enumeration = null;

    public function setSystemName(string $systemName): self
    {
        $this->system_name = $systemName;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setPresenter(string $presenter): self
    {
        $this->presenter = $presenter;

        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setParent(?Module $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }
}
