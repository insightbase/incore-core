<?php

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\LanguageSetting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LanguageSettingFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $count = count($manager->getRepository(LanguageSetting::class)->findAll());
        if($count === 0){
            $languageSetting = new LanguageSetting();
            $languageSetting->setType('url');
            $manager->persist($languageSetting);
            $manager->flush();
        }
    }
}