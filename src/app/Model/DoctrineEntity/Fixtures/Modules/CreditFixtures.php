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

class CreditFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $module = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'credit'])
        ;
        if (!$module) {
            $module = (new Module())
                ->setSystemName('credit')
                ->setName('Kredity')
                ->setPresenter('Credit')
                ->setAction('default')
                ->setIcon('ki-filled ki-dollar')
                ->setPosition(20)
            ;
            $manager->persist($module);
            $manager->flush();
        }

        $privilegeDefault = $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class);

        $modulePrivilegeRepository = $manager->getRepository(ModulePrivilege::class);
        if (!$modulePrivilegeRepository->findOneBy(['module' => $module, 'privilege' => $privilegeDefault])) {
            $modulePrivilege = new ModulePrivilege();
            $modulePrivilege->setModule($module);
            $modulePrivilege->setPrivilege($privilegeDefault);
            $manager->persist($modulePrivilege);
            $manager->flush();
        }

        $role = $this->getReference(RoleFixtures::ADMIN, Role::class);
        $permissionRepository = $manager->getRepository(Permission::class);
        if (!$permissionRepository->findOneBy(['module' => $module, 'privilege' => $privilegeDefault, 'role' => $role])) {
            $permission = new Permission();
            $permission->setModule($module);
            $permission->setPrivilege($privilegeDefault);
            $permission->setRole($role);
            $manager->persist($permission);
            $manager->flush();
        }
    }

    public function getDependencies(): array
    {
        return [
            PrivilegeFixtures::class,
            RoleFixtures::class,
        ];
    }
}
