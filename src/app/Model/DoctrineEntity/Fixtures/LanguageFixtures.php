<?php

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\Language;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LanguageFixtures extends Fixture implements FixtureInterface
{
    public const LANG_CS = 'language_lang_cs';

    public function load(ObjectManager $manager): void
    {
        $language = $manager->getRepository(Language::class)
            ->findOneBy(['locale' => 'cs-CZ'])
        ;
        if (!$language) {
            $language = new Language();
            $language->setName('Čeština');
            $language->setLocale('cs-CZ');
            $language->setIsDefault(true);
            $language->setUrl('cs');
            $manager->persist($language);
            $manager->flush();
        }
        $this->setReference(self::LANG_CS, $language);
    }
}
