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

class UserFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $users = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'users'])
        ;
        if (!$users) {
            $users = (new Module())
                ->setSystemName('users')
                ->setName('Uživatelé a role')
                ->setIcon('ki-filled ki-users text-lg')
                ->setPosition(7)
            ;
            $manager->persist($users);
            $manager->flush();
        }

        $usersUsers = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'users_users'])
        ;
        if (!$usersUsers) {
            $usersUsers = (new Module())
                ->setSystemName('users_users')
                ->setName('Uživatelé')
                ->setPresenter('User')
                ->setParent($users)
                ->setPosition(15)
            ;
            $manager->persist($usersUsers);
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
            if (!$modulePrivilegeRepository->findOneBy(['module' => $usersUsers, 'privilege' => $privilege])) {
                $modulePrivilege = new ModulePrivilege();
                $modulePrivilege->setModule($usersUsers);
                $modulePrivilege->setPrivilege($privilege);
                $manager->persist($modulePrivilege);
                $manager->flush();
            }
        }

        $role = $this->getReference(RoleFixtures::ADMIN, Role::class);
        $access = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
            $this->getReference(PrivilegeFixtures::EDIT, Privilege::class),
            $this->getReference(PrivilegeFixtures::NEW, Privilege::class),
            $this->getReference(PrivilegeFixtures::DELETE, Privilege::class),
        ];
        $permissionRepository = $manager->getRepository(Permission::class);
        foreach ($access as $privilege) {
            if (!$permissionRepository->findOneBy(['module' => $usersUsers, 'privilege' => $privilege, 'role' => $role])) {
                $permission = new Permission();
                $permission->setModule($usersUsers);
                $permission->setPrivilege($privilege);
                $permission->setRole($role);
                $manager->persist($permission);
                $manager->flush();
            }
        }

        $usersRole = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'users_role'])
        ;
        if (!$usersRole) {
            $usersRole = (new Module())
                ->setSystemName('users_role')
                ->setName('Role')
                ->setPresenter('Role')
                ->setParent($users)
                ->setPosition(16)
            ;
            $manager->persist($usersRole);
            $manager->flush();
        }
        $privileges = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
            $this->getReference(PrivilegeFixtures::NEW, Privilege::class),
            $this->getReference(PrivilegeFixtures::DELETE, Privilege::class),
            $this->getReference(PrivilegeFixtures::AUTHORIZATION, Privilege::class),
            $this->getReference(PrivilegeFixtures::SET, Privilege::class),
            $this->getReference(PrivilegeFixtures::EDIT, Privilege::class),
        ];
        foreach ($privileges as $privilege) {
            if (!$modulePrivilegeRepository->findOneBy(['module' => $usersRole, 'privilege' => $privilege])) {
                $modulePrivilege = new ModulePrivilege();
                $modulePrivilege->setModule($usersRole);
                $modulePrivilege->setPrivilege($privilege);
                $manager->persist($modulePrivilege);
                $manager->flush();
            }
        }

        $access = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
            $this->getReference(PrivilegeFixtures::NEW, Privilege::class),
            $this->getReference(PrivilegeFixtures::DELETE, Privilege::class),
            $this->getReference(PrivilegeFixtures::AUTHORIZATION, Privilege::class),
            $this->getReference(PrivilegeFixtures::SET, Privilege::class),
            $this->getReference(PrivilegeFixtures::EDIT, Privilege::class),
        ];
        $permissionRepository = $manager->getRepository(Permission::class);
        foreach ($access as $privilege) {
            if (!$permissionRepository->findOneBy(['module' => $usersRole, 'privilege' => $privilege, 'role' => $role])) {
                $permission = new Permission();
                $permission->setModule($usersRole);
                $permission->setPrivilege($privilege);
                $permission->setRole($role);
                $manager->persist($permission);
                $manager->flush();
            }
        }

        $privilege = $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class);
        if (!$permissionRepository->findOneBy(['module' => $users, 'privilege' => $privilege, 'role' => $role])) {
            $permission = new Permission();
            $permission->setModule($users);
            $permission->setPrivilege($privilege);
            $permission->setRole($role);
            $manager->persist($permission);
            $manager->flush();
        }
    }

    public function getDependencies(): array
    {
        return [
            PrivilegeFixtures::class,
        ];
    }
}
