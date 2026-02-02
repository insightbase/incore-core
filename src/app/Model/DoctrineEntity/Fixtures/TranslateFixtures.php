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
		'header_formHelp' => 'Nápověda',
		'flash_sendToTranslate' => 'Odeslat k překladu',
		'header_enumerationRecords %name%' => 'Počet záznamů: %name%',
		'input_type_editor_js' => 'HTML editor',
		'input_show_parent' => 'Zobrazovat možnost nastavit rodiče',
		'flash_enumerationItemFromContent' => 'Tento číselník se ovládá z modulu článků',
		'inputType_multiSelect' => 'Multi select',
		'inputType_date' => 'Datum',
		'inputType_checkboxList' => 'Checkbox list',
		'header_blog' => 'Blog',
		'header_blogEdit' => 'Editace',
		'header_blogNewCategory' => 'Nová kategorie',
		'header_blogEditCategory' => 'Editace',
		'header_blogCategory' => 'Kategorie',
		'header_blogNew' => 'Nový článek',
		'header_contentEditItemGallery' => 'Editace položky galerie',
		'blog_name' => 'Název',
		'blog_content' => 'Obsah',
		'flash_systemNameMustBeUnique' => 'Systémový název musí být unikátní',
		'input_entity' => 'Entita',
		'flash_blogDeleted' => 'Článek smazán',
		'flash_blogUpdataed' => 'Článek upraven',
		'flash_blogCreated' => 'Článek vytvořen',
		'flash_blogCategoryDeleted' => 'Kategorie smazána',
		'flash_blogCategoryEdited' => 'Kategorie upravena',
		'flash_blogCategoryCreated' => 'Kategorie vytvořena',
		'flash_blogCategoryNotFound' => 'Kategorie nebyla nalezena',
		'flash_blogNotFound' => 'Článek nebyl nalezen',
		'input_onePage' => 'Jednostránkový systém',
		'content_blockItemImage' => 'Obrázek',
		'content_blockItemTextArea' => 'Text area',
		'content_fieldTypeEditorJs' => 'HTML editor',
		'menu_newCategory' => 'Nová kategorie',
		'group_translates' => 'Překlady',
		'email.list' => 'Správa zpráv',
		'email.inAdmin' => 'Tuto zprávu, stejně jako další přijaté formuláře, můžete zobrazit ve své administraci. U každé zprávy máte možnost si označit, zda byla vyřešena.',
		'email.toAdmin' => 'Přejít do administrace',
		'email.generatted%url%' => 'Tento e-mail byl automaticky vygenerován systémem inCore na základě vyplnění kontaktního formuláře na webu %url%. Neodpovídejte na tento e-mail – slouží pouze k notifikaci.',
		'input_title' => 'Title',
		'performance_translateFromDefault' => 'Přeložit z výchozího jazyka',
		'performance_sendToTranslate' => 'Přeložit přes AI',
		'header_tag' => 'Tagy',
		'header_blogNewForm' => 'Nový článek',
		'header_tagEdit' => 'Editace tagu',
		'header_tagNew' => 'Nový tag',
		'blog_slug' => 'Slug',
		'blog_tags' => 'Tagy',
		'input_slug' => 'Slug',
		'flash_changeSlug%from%%to%' => 'Byl automaticky upraven slug z %from% na %to%',
		'flash_changeSlug%from%%to%%lang%' => 'Byl automaticky upraven slug z %from% na %to% pro jazyk %lang%',
		'menu_blogNew' => 'Nový článek',
		'column_slug' => 'Slug',
		'flash_tagDeleted' => 'Tag smazán',
		'flash_tagUpdated' => 'Tag upraven',
		'flash_tagCreated' => 'Tag vytvořen',
		'menu_tagNew' => 'Nový tag',
		'flash_tagNotFound' => 'Tag nebyl nalezen',
		'column_is_activeAdmin' => 'Pro překlady',
		'column_language_is_active' => 'Veřejný',
		'action_changeActiveAdmin' => null,
		'group_performance_global' => 'Obecné',
		'input_performance_global' => 'Obecné',
		'article_preview' => 'Náhled',
		'group_languageGeneral' => 'Obecné',
		'input_placeholder' => 'Placeholder',
		'input_titleSubpage' => 'Title pro subpage',
		'input_email_formReceiver' => 'Příjemce emailů',
		'inputType_radioList' => 'Radiolist',
		'group_general' => 'Obecné',
		'group_seo' => 'SEO',
		'input_actionTitle' => 'Title',
		'input_actionDescription' => 'Description',
		'input_actionKeywords' => 'Keywords',
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
