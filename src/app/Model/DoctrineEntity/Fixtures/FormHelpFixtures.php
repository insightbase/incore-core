<?php

declare(strict_types=1);

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\FormHelp;
use App\Model\DoctrineEntity\FormHelpLanguage;
use App\Model\DoctrineEntity\Language;

class FormHelpFixtures extends \Doctrine\Bundle\FixturesBundle\Fixture implements \Doctrine\Common\DataFixtures\FixtureInterface, \Doctrine\Common\DataFixtures\DependentFixtureInterface
{
	private array $formHelpsData = [];
	private array $languages;


	public function load(\Doctrine\Persistence\ObjectManager $manager): void
	{
		foreach($manager->getRepository(Language::class)->findAll() as $language){
		    $this->languages[$language->id] = $language;
		}

		foreach($this->formHelpsData as $formHelpData) {
		    $formHelp = $manager->getRepository(FormHelp::class)->findOneBy([
		        'id' => $formHelpData['id'],
		    ]);
		    $languages = $formHelpData['languages'];
		    unset($formHelpData['languages']);
		    if($formHelp === null){
		        $formHelp = new FormHelp();
		        $formHelp->presenter = $formHelpData['presenter'];
		        $formHelp->input = $formHelpData['input'];
		    }
		    $formHelp->label_help = $formHelpData['label_help'];
		    $manager->persist($formHelp);

		    foreach($languages as $formHelpLanguageData){
		        $formHelpLanguage = $manager->getRepository(FormHelpLanguage::class)->findOneBy([
		            'form_help' => $formHelp,
		            'language' => $this->languages[$formHelpLanguageData['language_id']],
		        ]);
		        if($formHelpLanguage === null){
		            $formHelpLanguage = new FormHelpLanguage();
		            $formHelpLanguage->form_help = $formHelp;
		            $formHelpLanguage->language = $this->languages[$formHelpLanguageData['language_id']];
		        }
		        $formHelpLanguage->label_help = $formHelpLanguageData['label_help'];
		        $manager->persist($formHelpLanguage);
		    }
		}
		$manager->flush();
	}


	public function getDependencies(): array
	{
		return [
		    LanguageFixtures::class,
		];
	}
}
