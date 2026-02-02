<?php

namespace App\Model\DoctrineEntity\Fixtures\Modules;

use App\Model\DoctrineEntity\Fixtures\PrivilegeFixtures;
use App\Model\DoctrineEntity\Fixtures\RoleFixtures;
use App\Model\DoctrineEntity\Module;
use App\Model\DoctrineEntity\ModulePrivilege;
use App\Model\DoctrineEntity\Permission;
use App\Model\DoctrineEntity\Privilege;
use App\Model\DoctrineEntity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StaticPageFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $module = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'staticPage']);
        if (!$module) {
            $module = (new Module())
                ->setSystemName('staticPage')
                ->setName('Statické stránky')
                ->setIcon('ki-solid ki-information')
                ->setPresenter('StaticPage')
                ->setPosition(30)
            ;
            $manager->persist($module);
            $manager->flush();
        }

        $privileges = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
            $this->getReference(PrivilegeFixtures::EDIT, Privilege::class),
            $this->getReference(PrivilegeFixtures::NEW, Privilege::class),
            $this->getReference(PrivilegeFixtures::DELETE, Privilege::class),
        ];
        $modulePrivilegeRepository = $manager->getRepository(ModulePrivilege::class);
        foreach ($privileges as $privilege) {
            if (!$modulePrivilegeRepository->findOneBy(['module' => $module, 'privilege' => $privilege])) {
                $modulePrivilege = new ModulePrivilege();
                $modulePrivilege->setModule($module);
                $modulePrivilege->setPrivilege($privilege);
                $manager->persist($modulePrivilege);
                $manager->flush();
            }
        }

        $role = $this->getReference(RoleFixtures::ADMIN, Role::class);
        $access = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
            $this->getReference(PrivilegeFixtures::EDIT, Privilege::class),
        ];
        $permissionRepository = $manager->getRepository(Permission::class);
        foreach ($access as $privilege) {
            if (!$permissionRepository->findOneBy(['module' => $module, 'privilege' => $privilege, 'role' => $role])) {
                $permission = new Permission();
                $permission->setModule($module);
                $permission->setPrivilege($privilege);
                $permission->setRole($role);
                $manager->persist($permission);
                $manager->flush();
            }
        }
    }

    public function getDependencies(): array
    {
        return [
            PrivilegeFixtures::class,
        ];
    }
}