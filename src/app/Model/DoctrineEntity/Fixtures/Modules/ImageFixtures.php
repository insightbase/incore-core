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

class ImageFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $images = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'images'])
        ;
        if (!$images) {
            $images = (new Module())
                ->setSystemName('images')
                ->setName('Obrázky')
                ->setIcon('ki-filled ki-files')
                ->setPresenter('Image')
                ->setPosition(11)
            ;
            $manager->persist($images);
            $manager->flush();
        }

        $privileges = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
            $this->getReference(PrivilegeFixtures::EDIT, Privilege::class),
            $this->getReference(PrivilegeFixtures::DELETE_UNUSED, Privilege::class),
        ];
        $modulePrivilegeRepository = $manager->getRepository(ModulePrivilege::class);
        foreach ($privileges as $privilege) {
            if (!$modulePrivilegeRepository->findOneBy(['module' => $images, 'privilege' => $privilege])) {
                $modulePrivilege = new ModulePrivilege();
                $modulePrivilege->setModule($images);
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