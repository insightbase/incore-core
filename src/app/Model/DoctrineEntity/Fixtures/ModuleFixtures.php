<?php

namespace App\Model\DoctrineEntity\Fixtures;;

use App\Component\Mail\SystemNameEnum;
use App\Model\DoctrineEntity\Email;
use App\Model\DoctrineEntity\Module;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ModuleFixtures implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $exist = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'users']);
        if(!$exist){
            $module = (new Module())
                ->setSystemName('users')
                ->setName('Uživatelé')
                ->setPresenter('User')
                ->setIcon('ki-filled ki-users text-lg')
            ;
            $manager->persist($module);
            $manager->flush();
        }

        $exist = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'languages']);
        if(!$exist){
            $module = (new Module())
                ->setSystemName('languages')
                ->setName('Jazyky')
                ->setPresenter('Language')
                ->setIcon('ki-filled ki-flag')
            ;
            $manager->persist($module);
            $manager->flush();
        }

        $exist = $manager->getRepository(Module::class)
            ->findOneBy(['system_name' => 'translates']);
        if(!$exist){
            $module = (new Module())
                ->setSystemName('translates')
                ->setName('Překlady')
                ->setPresenter('Translate')
                ->setIcon('ki-filled ki-geolocation')
            ;
            $manager->persist($module);
            $manager->flush();
        }
    }
}