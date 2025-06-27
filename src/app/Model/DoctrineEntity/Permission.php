<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'permission')]
class Permission implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Role $role;

    #[ORM\ManyToOne(targetEntity: Module::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Module $module;

    #[ORM\ManyToOne(targetEntity: Privilege::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Privilege $privilege;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    public bool $active = true;

    public function setRole(Role $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function setModule(Module $module): self
    {
        $this->module = $module;

        return $this;
    }

    public function setPrivilege(Privilege $privilege): self
    {
        $this->privilege = $privilege;

        return $this;
    }
}
