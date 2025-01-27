<?php

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\Privilege;
use App\Model\Enum\PrivilegeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrivilegeFixtures extends Fixture implements FixtureInterface
{
    public const DEFAULT = 'privilege-default';
    public const EDIT = 'privilege-edit';
    public const NEW = 'privilege-new';
    public const DELETE = 'privilege-delete';
    public const TRANSLATE = 'privilege-translate';
    public const SYNCHRONIZE = 'privilege-synchronize';
    public const AUTHORIZATION = 'privilege-authorization';
    public const SET = 'privilege-set';
    public const OWNER = 'privilege-owner';

    public function load(ObjectManager $manager): void
    {
        $privilegeRepository = $manager->getRepository(Privilege::class);

        $default = $privilegeRepository->findOneBy(['system_name' => PrivilegeEnum::Default->value]);
        if(!$default){
            $default = new Privilege();
            $default->setName('Default');
            $default->setSystemName(PrivilegeEnum::Default->value);
            $manager->persist($default);
            $manager->flush();
        }
        $this->addReference(self::DEFAULT, $default);

        $edit = $privilegeRepository->findOneBy(['system_name' => PrivilegeEnum::Edit->value]);
        if(!$edit){
            $edit = new Privilege();
            $edit->setName('Editace');
            $edit->setSystemName(PrivilegeEnum::Edit->value);
            $manager->persist($edit);
            $manager->flush();
        }
        $this->addReference(self::EDIT, $edit);

        $new = $privilegeRepository->findOneBy(['system_name' => PrivilegeEnum::New->value]);
        if(!$new){
            $new = new Privilege();
            $new->setName('Vytvoření');
            $new->setSystemName(PrivilegeEnum::New->value);
            $manager->persist($new);
            $manager->flush();
        }
        $this->addReference(self::NEW, $new);

        $delete = $privilegeRepository->findOneBy(['system_name' => PrivilegeEnum::Delete->value]);
        if(!$delete){
            $delete = new Privilege();
            $delete->setName('Smazání');
            $delete->setSystemName(PrivilegeEnum::Delete->value);
            $manager->persist($delete);
            $manager->flush();
        }
        $this->addReference(self::DELETE, $delete);

        $translate = $privilegeRepository->findOneBy(['system_name' => PrivilegeEnum::Translate->value]);
        if(!$translate){
            $translate = new Privilege();
            $translate->setName('Přeložit');
            $translate->setSystemName(PrivilegeEnum::Translate->value);
            $manager->persist($translate);
            $manager->flush();
        }
        $this->addReference(self::TRANSLATE, $translate);

        $synchronize = $privilegeRepository->findOneBy(['system_name' => PrivilegeEnum::Synchronize->value]);
        if(!$synchronize){
            $synchronize = new Privilege();
            $synchronize->setName('Synchronizovat');
            $synchronize->setSystemName(PrivilegeEnum::Synchronize->value);
            $manager->persist($synchronize);
            $manager->flush();
        }
        $this->addReference(self::SYNCHRONIZE, $synchronize);

        $authorization = $privilegeRepository->findOneBy(['system_name' => PrivilegeEnum::Authorization->value]);
        if(!$authorization){
            $authorization = new Privilege();
            $authorization->setName('Oprávnění');
            $authorization->setSystemName(PrivilegeEnum::Authorization->value);
            $manager->persist($authorization);
            $manager->flush();
        }
        $this->addReference(self::AUTHORIZATION, $authorization);

        $set = $privilegeRepository->findOneBy(['system_name' => PrivilegeEnum::Set->value]);
        if(!$set){
            $set = new Privilege();
            $set->setName('Nastavit');
            $set->setSystemName(PrivilegeEnum::Set->value);
            $manager->persist($set);
            $manager->flush();
        }
        $this->addReference(self::SET, $set);
    }
}