<?php

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\Privilege;
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

    public function load(ObjectManager $manager): void
    {
        $privilegeRepository = $manager->getRepository(Privilege::class);

        $default = $privilegeRepository->findOneBy(['system_name' => 'default']);
        if(!$default){
            $default = new Privilege();
            $default->setName('Default');
            $default->setSystemName('default');
            $manager->persist($default);
            $manager->flush();
        }
        $this->addReference(self::DEFAULT, $default);

        $edit = $privilegeRepository->findOneBy(['system_name' => 'edit']);
        if(!$edit){
            $edit = new Privilege();
            $edit->setName('Editace');
            $edit->setSystemName('edit');
            $manager->persist($edit);
            $manager->flush();
        }
        $this->addReference(self::EDIT, $edit);

        $new = $privilegeRepository->findOneBy(['system_name' => 'new']);
        if(!$new){
            $new = new Privilege();
            $new->setName('Vytvoření');
            $new->setSystemName('new');
            $manager->persist($new);
            $manager->flush();
        }
        $this->addReference(self::NEW, $new);

        $delete = $privilegeRepository->findOneBy(['system_name' => 'delete']);
        if(!$delete){
            $delete = new Privilege();
            $delete->setName('Smazání');
            $delete->setSystemName('delete');
            $manager->persist($delete);
            $manager->flush();
        }
        $this->addReference(self::DELETE, $delete);

        $translate = $privilegeRepository->findOneBy(['system_name' => 'translate']);
        if(!$translate){
            $translate = new Privilege();
            $translate->setName('Přeložit');
            $translate->setSystemName('translate');
            $manager->persist($translate);
            $manager->flush();
        }
        $this->addReference(self::TRANSLATE, $translate);

        $synchronize = $privilegeRepository->findOneBy(['system_name' => 'synchronize']);
        if(!$synchronize){
            $synchronize = new Privilege();
            $synchronize->setName('Synchronizovat');
            $synchronize->setSystemName('synchronize');
            $manager->persist($synchronize);
            $manager->flush();
        }
        $this->addReference(self::SYNCHRONIZE, $synchronize);
    }
}