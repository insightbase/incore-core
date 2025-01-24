<?php

namespace App\Model\DoctrineEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'module_privilege')]
class ModulePrivilege implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Module::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Module $module;

    #[ORM\ManyToOne(targetEntity: Privilege::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Privilege $privilege;

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