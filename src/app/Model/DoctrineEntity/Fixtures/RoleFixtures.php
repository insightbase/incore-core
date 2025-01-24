<?php

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\Role;
use App\Model\DoctrineEntity\User;
use App\Model\Enum\RoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture implements FixtureInterface
{
    public const ADMIN = 'role-admin';

    public function load(ObjectManager $manager): void
    {
        $exist = $manager->getRepository(Role::class)->findOneBy(['system_name' => RoleEnum::SUPER_ADMIN->value]);

        if(!$exist){
            $role = (new Role())
                ->setName('Super administrátor')
                ->setSystemName(RoleEnum::SUPER_ADMIN->value)
                ->setIsSystemic(true)
            ;
            $manager->persist($role);
        }

        $admin = $manager->getRepository(Role::class)->findOneBy(['system_name' => RoleEnum::ADMIN->value]);
        if(!$admin) {
            $admin = (new Role())
                ->setName('Administrátor')
                ->setSystemName(RoleEnum::ADMIN->value)
                ->setIsSystemic(true)
            ;
            $manager->persist($admin);
            $manager->flush();
        }
        $this->setReference(self::ADMIN, $admin);
    }
}