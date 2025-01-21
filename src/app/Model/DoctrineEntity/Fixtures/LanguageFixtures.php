<?php

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\Language;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LanguageFixtures implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $exist = $manager->getRepository(Language::class)
            ->findOneBy(['locale' => 'cs-CZ']);
        if(!$exist){
            $language = new Language();
            $language->setName('Čeština');
            $language->setLocale('cs-CZ');
            $language->setIsDefault(true);
            $language->setUrl('cs');
            $manager->persist($language);
            $manager->flush();
        }
    }
}