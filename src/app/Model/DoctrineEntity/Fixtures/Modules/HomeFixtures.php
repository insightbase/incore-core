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
    }

    public function getDependencies(): array
    {
        return [
            PrivilegeFixtures::class,
        ];
    }
}