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

class SettingFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $role = $this->getReference(RoleFixtures::ADMIN, Role::class);
        $permissionRepository = $manager->getRepository(Permission::class);

        $setting = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'setting'])
        ;
        if (!$setting) {
            $setting = (new Module())
                ->setSystemName('setting')
                ->setName('Nastavení')
                ->setIcon('ki-filled ki-setting-2')
                ->setPosition(8)
            ;
            $manager->persist($setting);
            $manager->flush();
        }

        $privilegeDefault = $this->getReference(PrivilegeFixtures::DEFAULT, Privilege::class);
        if (!$permissionRepository->findOneBy(['module' => $setting, 'privilege' => $privilegeDefault, 'role' => $role])) {
            $permission = new Permission();
            $permission->setModule($setting);
            $permission->setPrivilege($privilegeDefault);
            $permission->setRole($role);
            $manager->persist($permission);
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
                ->setPosition(12)
            ;
            $manager->persist($settingSetting);
            $manager->flush();
        }

        $settingAnalytics = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'setting_analytics'])
        ;
        if (!$settingAnalytics) {
            $settingAnalytics = (new Module())
                ->setSystemName('setting_analytics')
                ->setName('Analytics')
                ->setPresenter('Setting')
                ->setAction('analytics')
                ->setParent($setting)
                ->setPosition(17)
            ;
            $manager->persist($settingAnalytics);
            $manager->flush();
        }

        $privilegeAnalytics = $manager->getRepository(Privilege::class)->findOneBy(['system_name' => 'analytics']);
        if (!$privilegeAnalytics) {
            $privilegeAnalytics = (new Privilege());
            $privilegeAnalytics->setSystemName('analytics');
            $privilegeAnalytics->setName('Analytics');
            $manager->persist($privilegeAnalytics);
            $manager->flush();
        }

        $privileges = [
            $privilegeDefault,
            $privilegeAnalytics,
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

        if (!$permissionRepository->findOneBy(['module' => $settingAnalytics, 'privilege' => $privilegeAnalytics, 'role' => $role])) {
            $permission = new Permission();
            $permission->setModule($settingAnalytics);
            $permission->setPrivilege($privilegeAnalytics);
            $permission->setRole($role);
            $manager->persist($permission);
            $manager->flush();
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
                ->setPosition(13)
            ;
            $manager->persist($settingModule);
            $manager->flush();
        }

        $privileges = [
            $privilegeDefault,
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
                ->setPosition(14)
            ;
            $manager->persist($settingFavicon);
            $manager->flush();
        }

        $privileges = [
            $privilegeDefault,
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
