<?php

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\Role;
use App\Model\DoctrineEntity\User;
use App\Model\Enum\RoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $email = 'stodulka@insightbase.cz';

        $role = $manager->getRepository(Role::class)->findOneBy(['system_name' => RoleEnum::SUPER_ADMIN->value]);
        if (!$role) {
            return;
        }

        $exist = $manager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$exist) {
            $user = new User();
            $user->firstname = 'Václav';
            $user->lastname = 'Stodůlka';
            $user->email = $email;
            $user->password = '$2y$12$DlMknNVujMl9mcv2vvBp6uw3goQcF328uEMKEt1gEfuUQXZZ5T8R6';
            $user->role = $role;

            $manager->persist($user);
            $manager->flush();
        }
    }

    public function getDependencies(): array
    {
        return [
            RoleFixtures::class,
        ];
    }
}
