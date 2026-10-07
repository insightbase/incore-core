<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Příznak, že role má v modulu omezení na vybrané položky (seznam je v permission_item).
 * Je v samostatné tabulce, aby smazání poslední povolené položky roli neodemklo celý modul.
 */
#[ORM\Entity]
#[ORM\Table(name: 'permission_item_restriction')]
#[ORM\UniqueConstraint(name: 'permission_item_restriction_role_module', columns: ['role_id', 'module_id'])]
class PermissionItemRestriction implements Entity
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
}
