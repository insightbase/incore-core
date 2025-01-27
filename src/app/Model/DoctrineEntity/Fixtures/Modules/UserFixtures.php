<?php

namespace App\Model\DoctrineEntity\Fixtures\Modules;

use App\Model\DoctrineEntity\Fixtures\PrivilegeFixtures;
use App\Model\DoctrineEntity\Module;
use App\Model\DoctrineEntity\ModulePrivilege;
use App\Model\DoctrineEntity\Privilege;
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
            $module = (new Module())
                ->setSystemName('users')
                ->setName('Uživatelé a role')
                ->setIcon('ki-filled ki-users text-lg')
            ;
            $manager->persist($module);
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
            ;
            $manager->persist($usersUsers);
            $manager->flush();
        }

        $privileges = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
            $this->getReference(PrivilegeFixtures::EDIT, Privilege::class),
            $this->getReference(PrivilegeFixtures::NEW, Privilege::class),
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

        $usersRole = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'users_role'])
        ;
        if (!$usersRole) {
            $usersRole = (new Module())
                ->setSystemName('users_role')
                ->setName('Role')
                ->setPresenter('Role')
                ->setParent($users)
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
    }

    public function getDependencies(): array
    {
        return [
            PrivilegeFixtures::class,
        ];
    }
}
