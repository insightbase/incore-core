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

class SettingFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $setting = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'setting'])
        ;
        if (!$setting) {
            $setting = (new Module())
                ->setSystemName('setting')
                ->setName('Nastavení')
                ->setIcon('ki-filled ki-setting-2')
            ;
            $manager->persist($setting);
            $manager->flush();
        }

        $settingSetting = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'setting_setting'])
        ;
        if (!$settingSetting) {
            $settingSetting = (new Module())
                ->setSystemName('setting_setting')
                ->setName('Obecné')
                ->setPresenter('Setting')
                ->setParent($setting)
            ;
            $manager->persist($settingSetting);
            $manager->flush();
        }

        $privileges = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
        ];
        $modulePrivilegeRepository = $manager->getRepository(ModulePrivilege::class);
        foreach ($privileges as $privilege) {
            if (!$modulePrivilegeRepository->findOneBy(['module' => $settingSetting, 'privilege' => $privilege])) {
                $modulePrivilege = new ModulePrivilege();
                $modulePrivilege->setModule($settingSetting);
                $modulePrivilege->setPrivilege($privilege);
                $manager->persist($modulePrivilege);
                $manager->flush();
            }
        }

        $settingModule = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'setting_module'])
        ;
        if (!$settingModule) {
            $settingModule = (new Module())
                ->setSystemName('setting_module')
                ->setName('Moduly')
                ->setPresenter('Module')
                ->setParent($setting)
            ;
            $manager->persist($settingModule);
            $manager->flush();
        }

        $privileges = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
            $this->getReference(PrivilegeFixtures::EDIT, Privilege::class),
        ];
        $modulePrivilegeRepository = $manager->getRepository(ModulePrivilege::class);
        foreach ($privileges as $privilege) {
            if (!$modulePrivilegeRepository->findOneBy(['module' => $settingModule, 'privilege' => $privilege])) {
                $modulePrivilege = new ModulePrivilege();
                $modulePrivilege->setModule($settingModule);
                $modulePrivilege->setPrivilege($privilege);
                $manager->persist($modulePrivilege);
                $manager->flush();
            }
        }

        $settingFavicon = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'setting_favicon'])
        ;
        if (!$settingFavicon) {
            $settingFavicon = (new Module())
                ->setSystemName('setting_favicon')
                ->setName('Favicon')
                ->setPresenter('Favicon')
                ->setParent($setting)
            ;
            $manager->persist($settingFavicon);
            $manager->flush();
        }

        $privileges = [
            $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class),
            $this->getReference(PrivilegeFixtures::EDIT, Privilege::class),
            $this->getReference(PrivilegeFixtures::NEW, Privilege::class),
            $this->getReference(PrivilegeFixtures::IMPORT, Privilege::class),
        ];
        $modulePrivilegeRepository = $manager->getRepository(ModulePrivilege::class);
        foreach ($privileges as $privilege) {
            if (!$modulePrivilegeRepository->findOneBy(['module' => $settingFavicon, 'privilege' => $privilege])) {
                $modulePrivilege = new ModulePrivilege();
                $modulePrivilege->setModule($settingFavicon);
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
