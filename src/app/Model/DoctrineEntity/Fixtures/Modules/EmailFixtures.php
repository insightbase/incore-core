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

class EmailFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $email = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'emails']);
        if (!$email) {
            $email = (new Module())
                ->setSystemName('emails')
                ->setName('Emaily')
                ->setIcon('ki-filled ki-mail')
                ->setPresenter('Email')
                ->setPosition(10)
            ;
            $manager->persist($email);
            $manager->flush();
        }

        $privileges = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
            $this->getReference(PrivilegeFixtures::EDIT, Privilege::class),
            $this->getReference(PrivilegeFixtures::NEW, Privilege::class),
        ];
        $modulePrivilegeRepository = $manager->getRepository(ModulePrivilege::class);
        foreach ($privileges as $privilege) {
            if (!$modulePrivilegeRepository->findOneBy(['module' => $email, 'privilege' => $privilege])) {
                $modulePrivilege = new ModulePrivilege();
                $modulePrivilege->setModule($email);
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