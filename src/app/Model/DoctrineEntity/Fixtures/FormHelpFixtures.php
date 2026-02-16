<?php

declare(strict_types=1);

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\FormHelp;
use App\Model\DoctrineEntity\FormHelpLanguage;
use App\Model\DoctrineEntity\Language;

class FormHelpFixtures extends \Doctrine\Bundle\FixturesBundle\Fixture implements \Doctrine\Common\DataFixtures\FixtureInterface, \Doctrine\Common\DataFixtures\DependentFixtureInterface
{
	private array $formHelpsData = [
		[
			'id' => 1,
			'presenter' => 'Admin:Setting',
			'input' => 'frm-formEdit-logo_id',
			'label_help' => '136 x 22px',
			'languages' => [
				['label_help' => '', 'language_id' => 2, 'form_help_id' => 1],
				['label_help' => '', 'language_id' => 5, 'form_help_id' => 1],
			],
		],
		[
			'id' => 2,
			'presenter' => 'Admin:Setting',
			'input' => 'frm-formEdit-logo_dark_id',
			'label_help' => '136 x 22px',
			'languages' => [
				['label_help' => '', 'language_id' => 2, 'form_help_id' => 2],
				['label_help' => '', 'language_id' => 5, 'form_help_id' => 2],
			],
		],
		[
			'id' => 3,
			'presenter' => 'Admin:Setting',
			'input' => 'frm-formEdit-logo_small_id',
			'label_help' => '24 x 22px',
			'languages' => [
				['label_help' => '', 'language_id' => 2, 'form_help_id' => 3],
				['label_help' => '', 'language_id' => 5, 'form_help_id' => 3],
			],
		],
		[
			'id' => 4,
			'presenter' => 'Admin:Setting',
			'input' => 'frm-formEdit-logo_dark_small_id',
			'label_help' => '24 x 22px',
			'languages' => [
				['label_help' => '', 'language_id' => 2, 'form_help_id' => 4],
				['label_help' => '', 'language_id' => 5, 'form_help_id' => 4],
			],
		],
		[
			'id' => 5,
			'presenter' => 'Admin:ContactForm',
			'input' => 'frm-formEdit-receiver',
			'label_help' => 'Více příjemců lze oddělit středníkem',
			'languages' => [
				['label_help' => '', 'language_id' => 2, 'form_help_id' => 5],
				['label_help' => '', 'language_id' => 5, 'form_help_id' => 5],
			],
		],
	];

	private array $languages;


	public function load(\Doctrine\Persistence\ObjectManager $manager): void
	{
		foreach($manager->getRepository(Language::class)->findAll() as $language){
		    $this->languages[$language->id] = $language;
		}

		foreach($this->formHelpsData as $formHelpData) {
		    $formHelp = $manager->getRepository(FormHelp::class)->findOneBy([
		        'presenter' => $formHelpData['presenter'],
		        'input' => $formHelpData['input'],
		    ]);
		    $languages = $formHelpData['languages'];
		    unset($formHelpData['languages']);
		    if($formHelp === null){
		        $formHelp = new FormHelp();
		    }
		    $formHelp->presenter = $formHelpData['presenter'];
		    $formHelp->input = $formHelpData['input'];
		    $formHelp->label_help = $formHelpData['label_help'];
		    $manager->persist($formHelp);

		    foreach($languages as $formHelpLanguageData){
		        if(array_key_exists($formHelpLanguageData['language_id'], $this->languages)){
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
