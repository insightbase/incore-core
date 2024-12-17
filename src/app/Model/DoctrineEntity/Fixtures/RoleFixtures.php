<?php

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\Role;
use App\Model\DoctrineEntity\User;
use App\Model\Enum\RoleEnum;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures implements FixtureInterface
{

    public function load(ObjectManager $manager): void
    {
        $exist = $manager->getRepository(Role::class)->findOneBy(['system_name' => 'super_admin']);

        if(!$exist){
            $role = (new Role())
                ->setName('Super administrátor')
                ->setSystemName(RoleEnum::SUPER_ADMIN->value)
            ;
            $manager->persist($role);

            $role = (new Role())
                ->setName('Administrátor')
                ->setSystemName(RoleEnum::ADMIN->value)
            ;
            $manager->persist($role);
            $manager->flush();
        }
    }
}