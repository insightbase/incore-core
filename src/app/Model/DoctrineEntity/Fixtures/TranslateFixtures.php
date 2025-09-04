<?php

declare(strict_types=1);

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\Language;
use App\Model\DoctrineEntity\Translate;
use App\Model\DoctrineEntity\TranslateLanguage;

class TranslateFixtures extends \Doctrine\Bundle\FixturesBundle\Fixture implements \Doctrine\Common\DataFixtures\FixtureInterface, \Doctrine\Common\DataFixtures\DependentFixtureInterface
{
	private array $translates = [
		'flash_userDeleted' => 'Uživatel smazán',
		'flash_userCannotByDeleted' => 'Tento uživatel nemůže být smazán',
		'header_contentEdit' => 'Editace článku',
		'header_content' => 'Články',
		'header_newContent' => 'Nový článek',
		'header_contentNew' => 'Nový článek',
		'header_contentNewGroup' => 'Nová skupina',
		'header_contentEditGroup' => 'Editace skupiny',
		'header_contentDetail' => 'Detail článku',
		'header_contentEditItem' => 'Editace bloku',
		'column_systemName' => 'Systémový název',
		'menu_detail' => 'Detail',
		'content_typeText' => 'Text',
		'content_typeTextArea' => 'Textarea',
		'input_date' => 'Datum',
		'error_systemNameAlreadyExists' => 'Takový systémový název už existuje',
		'flash_contentGroupUpdated' => 'Skupina upravena',
		'flash_contentGroupCreated' => 'Skupina vytvořena',
		'flash_contentItemNotFound' => 'Blok nebyl nalezen',
		'flash_contentGroupNotFound' => 'Skupina nebyla nalezena',
		'flash_contentNotFound' => 'Článek nebyl nalezen',
		'flash_contentGroupDeleted' => 'Skupina smazána',
		'flash_contentDeleted' => 'Článek smazán',
		'flash_contentUpdated' => 'Článek upraven',
		'flash_contentCreated' => 'Článek vytvořen',
		'content_typeContent' => 'Článek',
		'content_typeEnumeration' => 'Číselník',
		'content_typeContentGroup' => 'Skupina článků',
		'content_typeGallery' => 'Galerie',
		'input_images' => 'Obrázky',
		'content_typeHtml' => 'HTML',
		'column_parent' => 'Nadřazená položka',
		'input_enumeration' => 'Číselník',
		'input_selectNoParent' => 'Bez nadřazené položky',
		'header_contentEditBlock' => 'Editace bloku',
		'header_newContentBlock' => 'Nový blok',
		'header_newContentField' => 'Nové pole',
		'header_contentEditValue' => 'Editace hodnoty',
		'header_contentNewEnumeration' => 'Nový číselník',
		'header_contentEditEnumeration' => 'Editace číselníku',
		'content_blockItemEnumeration' => 'Číselník',
		'content_blockItemContentGroup' => 'Skupina článků',
		'content_blockItemText' => 'Text',
		'content_blockItemGallery' => 'Galerie',
		'content_blockItemEditorJs' => 'HTML Editor',
		'input_detailRenderer' => 'Renderer',
		'input_enumerationNotSelected' => 'Číselník není vybrán',
		'input_contentGroup' => 'Skupina článků',
		'input_contentGroupNotSelected' => 'Skupina článků není vybrána',
		'input_block' => 'Blok',
		'input_typeBlock' => 'Blok',
		'input_typeCustom' => 'Vlastní',
		'input_selectContentNotConnection' => 'Článek není připojen',
		'group_boxes' => 'Boxy',
		'input_selectItems' => 'Vybrat položky',
		'content_fieldTypeSelect' => 'Select',
		'content_fieldTypeText' => 'Text',
		'flash_contentValueItemNotFound' => 'Položka nebyla nalezena',
		'flash_contentFieldCreated' => 'Pole vytvořeno',
		'flash_contentBlockItemUpdated' => 'Blok upraven',
		'flash_contentBlockDeleted' => 'Blok smazán',
		'flash_contentBlockItemGalleryNotFound' => 'Galerie nebyla nalezena',
		'flash_contentValueNotFound' => 'Hodnota nebyla nalezena',
		'flash_contentBlockNotFound' => 'Blok nebyl nalezen',
		'flash_contentBlockItemNotFound' => 'Hodnota bloku nebyla nalezena',
		'flash_contentBlockCreated' => 'Blok vytvořen',
		'menu_addBlock' => 'Nový blok',
		'menu_addField' => 'Nové pole',
		'header_performanceContent' => 'Články',
		'menu_performance' => 'Performance',
		'menu_content' => 'Články',
		'column_title' => 'Title',
		'header_performanceTranslates' => 'Překlady',
		'menu_translates' => 'Překlady',
		'content_fieldTypeDropzone' => 'Dropzone',
		'input_defaultType' => 'Výchozí typ',
		'input_hasContent' => 'Obsahuje obsah',
		'input_enabledCreateForAdmin' => 'Povolit vytváření/mazání pro admina',
		'content_fieldTypeCheckbox' => 'Checkbox',
		'content_fieldTypeTextArea' => 'TextArea',
		'content_emptyName' => 'Nový článek',
		'column_targetId' => 'Ovlivněné ID',
		'input_type_select' => 'Výběr hodnot',
		'home_googleAnalyticsError' => 'Chyba v získávání dat z Google Analytics',
		'header_analytics' => 'Google Analytics',
		'flash_setting_analyticsUpdated' => 'Google Analytics nastaveno',
		'flash_translationInProgress' => 'Texty byly odeslány k překladu',
		'flash_basicAuthNotSet' => 'Váš web vyžaduje Basic Authentication, pro funkci překladu musíte nastavit přístup v nastavení',
		'field_basicAuth' => 'Basic Authentication',
		'input_basicAuthUser' => 'Uživatel',
		'input_basicAuthPassword' => 'Heslo',
		'flash_anotherTranslationInProgress' => 'Překlad tohoto jazyka probíhá. Prosím počktejte.',
		'input_translate_expand_keys' => 'Překlady - rozpadnout menu podle klíčů',
		'input_active' => 'Aktivní',
		'column_active' => 'Aktivní',
	];


	public function load(\Doctrine\Persistence\ObjectManager $manager): void
	{
		$language = $this->getReference(LanguageFixtures::LANG_CS, Language::class);
		        foreach($this->translates as $key => $value){
		            $translate = $manager->getRepository(Translate::class)->findOneBy(['key' => $key]);
		            if($translate === null){
		                $translate = new Translate();
		                $translate->setKey($key);
		                $translate->source = 'admin';
		                $manager->persist($translate);
		            }

		            if($value !== null){
		                $translateLanguage = $manager->getRepository(TranslateLanguage::class)->findOneBy([
		                    'translate' => $translate,
		                    'language' => $language,
		                ]);
		                if($translateLanguage === null) {
		                    $translateLanguage = new TranslateLanguage();
		                    $translateLanguage->setLanguage($language);
		                    $translateLanguage->setTranslate($translate);
		                    $translateLanguage->setValue($value);
		                }else{
		                    $translateLanguage->setValue($value);
		                }
		                $manager->persist($translateLanguage);
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
