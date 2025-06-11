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

class HomeFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $home = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'home']);
        if (!$home) {
            $home = (new Module())
                ->setSystemName('home')
                ->setName('Dashboard')
                ->setIcon('ki-filled ki-element-11 text-lg')
                ->setPresenter('Home')
                ->setPosition(1)
            ;
            $manager->persist($home);
            $manager->flush();
        }

        $privileges = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
        ];
        $modulePrivilegeRepository = $manager->getRepository(ModulePrivilege::class);
        foreach ($privileges as $privilege) {
            if (!$modulePrivilegeRepository->findOneBy(['module' => $home, 'privilege' => $privilege])) {
                $modulePrivilege = new ModulePrivilege();
                $modulePrivilege->setModule($home);
                $modulePrivilege->setPrivilege($privilege);
                $manager->persist($modulePrivilege);
                $manager->flush();
            }
        }

        $role = $this->getReference(RoleFixtures::ADMIN, Role::class);
        $access = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
        ];
        $permissionRepository = $manager->getRepository(Permission::class);
        foreach ($access as $privilege) {
            if (!$permissionRepository->findOneBy(['module' => $home, 'privilege' => $privilege, 'role' => $role])) {
                $permission = new Permission();
                $permission->setModule($home);
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