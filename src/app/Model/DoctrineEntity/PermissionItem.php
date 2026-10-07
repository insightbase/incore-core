<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Položka modulu, se kterou smí omezená role pracovat. item_id je ID v tabulce modulu (bez FK).
 */
#[ORM\Entity]
#[ORM\Table(name: 'permission_item')]
#[ORM\UniqueConstraint(name: 'permission_item_role_module_item', columns: ['role_id', 'module_id', 'item_id'])]
#[ORM\Index(name: 'permission_item_module_item', columns: ['module_id', 'item_id'])]
class PermissionItem implements Entity
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

    #[ORM\Column(type: 'integer')]
    public int $item_id;
}
