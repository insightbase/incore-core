<?php

declare(strict_types=1);

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\Language;
use App\Model\DoctrineEntity\Translate;
use App\Model\DoctrineEntity\TranslateLanguage;

class TranslateFixtures extends \Doctrine\Bundle\FixturesBundle\Fixture implements \Doctrine\Common\DataFixtures\FixtureInterface, \Doctrine\Common\DataFixtures\DependentFixtureInterface
{
	private array $translates = [
		'my_account' => 'Můj účet',
		'languages' => 'Jazyky',
		'new_language' => 'Nový jazyk',
		'edit_language' => 'Upravit jazyk',
		'change_password' => 'Změna hesla',
		'changePassword_checkEmail' => 'Zadejte email',
		'changePassword_clickOnLinkInEmail' => 'Klikněte na odkaz, který Vám byl poslán emailem',
		'changePassword_forSetNewPassword' => 'Pro nastavení nového hesla',
		'changePassword_notReceived' => 'Pokud email nepřišel',
		'changePassword_sendAgain' => 'Poslat znovu',
		'forgotPasword_input_email' => 'Email',
		'forgotPasword_input_emailForReset' => 'Email pro resetování hesla',
		'login_forgotPassword' => 'Zapomenuté heslo',
		'changePassword_changed' => 'Heslo změněno',
		'changePassword_done' => 'Hotovo - heslo bylo změněno',
		'changePassword_safety' => 'Myslíme na Vaši bezpečnost',
		'changePassword_linkToLogin' => 'Přihlásit se',
		'header_resetPassword' => 'Reset hesla',
		'resetPassword_newPassword' => 'Nové heslo',
		'header_translates' => 'Překlady',
		'header_translateKey%key%' => 'Překlad klíče %key%',
		'flash_userNotFound' => 'Uživatel nebyl nalezen',
		'flash_badPassword' => 'Špatné heslo',
		'flash_fileSaveFailed' => 'Soubor se nepodařilo uložit',
		'flash_internalError' => 'Vnitřní chyba, kontaktujte administrátora',
		'id' => 'ID',
		'column_name' => 'Jméno',
		'column_locale' => 'Locale',
		'menu_edit' => 'Upravit',
		'menu_delete' => 'Smazat',
		'submit_create' => 'Vytvořit',
		'input_name' => 'Jméno',
		'input_locale' => 'Locale',
		'input_flag' => 'Vlajka',
		'submit_edit' => 'Upravit',
		'flash_languageNotFound' => 'Jazyk nebyl nalezen',
		'flash_languageDeleted' => 'Jazyk byl smazán',
		'menu_newLanguage' => 'Nový jazyk',
		'flash_languageUpdated' => 'Jazyk upraven',
		'flash_languageCreated' => 'Jazyk vytvořen',
		'input_newPassword' => 'Nové heslo',
		'input_newPasswordCheck' => 'Nové heslo (kontrola)',
		'error_bothPasswordsMustBeSame' => 'Obě hesla musí být stejná',
		'submit_changePassword' => 'Změnit heslo',
		'input_firstName' => 'Jméno',
		'input_lastName' => 'Příjmení',
		'input_email' => 'Email',
		'error_emailAlreadyExists' => 'Takový email už existuje',
		'input_avatar' => 'Avatar',
		'input_update' => 'Upravit',
		'flash_passwordChanged' => 'Heslo změněno',
		'flash_updated' => 'Upraveno',
		'input_confimNewPassword' => 'Potvrzení nového hesla',
		'error_bothPasswordMusetBeSame' => 'Obě hesla musí být stejná',
		'submit_set' => 'Nastavit',
		'submit_continue' => 'Pokračovat',
		'input_password' => 'Heslo',
		'input_rememberMe' => 'Zapamatovat si mě',
		'submit_logIn' => 'Přihlásit se',
		'flash_hasNotFound' => 'Hash nebyl nalezen',
		'flash_linkNotValid' => 'Odkaz je neplatný',
		'flash_userLoggedOut' => 'Uživatel odhlášen',
		'column_key' => 'Klíč',
		'menu_translate' => 'Přeložit',
		'submit_translate' => 'Přeložit',
		'menu_synchronize' => 'Synchronizovat',
		'flash_translateSet' => 'Překlad nastaven',
		'flash_keyNotFound' => 'Takový klíč nebyl nalezen',
		'paginator_show %perPage% from %count%' => 'Zobrazuji %perPage% z %count%',
		'button_export' => 'Export',
		'input_search' => 'Hledat',
		'%itemFrom%-%itemTo% z %count%' => '%itemFrom%-%itemTo% z %count%',
		'flash_badLink' => 'Špatný odkaz',
		'header_contactForms' => 'Formuláře',
		'header_newForm' => 'Nový formulář',
		'header_formEdit' => 'Editace formuláře',
		'header_formRecords %name%' => 'Záznamy formuláře %name%',
		'header_newUser' => 'Nový uživatel',
		'inputType_text' => 'Text',
		'inputType_checkbox' => 'Checkbox',
		'inputType_textArea' => 'TextArea',
		'inputType_email' => 'Email',
		'inputType_select' => 'Select',
		'flash_formNotFound' => 'Formulář nebyl nalezen',
		'flash_formUpdated' => 'Formulář upraven',
		'flash_formCreated' => 'Formulář vytvořen',
		'menu_newForm' => 'Nový ',
		'column_countNotSolved' => 'Nevyřešených',
		'menu_records' => 'Záznamy',
		'menu_update' => 'Upravit',
		'column_dateSend' => 'Datum odeslání',
		'column_solved' => 'Vyřešeno',
		'input_type' => 'Typ',
		'input_required' => 'Povinný',
		'input_showInGrid' => 'Zobrazovat ve výpisu',
		'button_addItem' => 'Přidat',
		'input_label' => 'Label',
		'button_addNewRow' => 'Přidat nový',
		'header_users' => 'Uživatelé',
		'column_firstname' => 'Jméno',
		'column_lastname' => 'Příjmení',
		'column_email' => 'Email',
		'input_firstname' => 'Jméno',
		'input_lastname' => 'Příjmení',
		'submit_update' => 'Upravit',
		'menu_newUser' => 'Nový',
		'flash_userUpdated' => 'Uživatel upraven',
		'flash_userCreated' => 'Uživatel vytvořen',
		'column_is_default' => 'Výchozí',
		'flash_default_language_required' => 'Musí být nastaven výchozí jazyk',
		'column_url' => 'URL',
		'column_is_active' => 'Aktivní',
		'input_url' => 'URL',
		'input_url_max_length_%length%' => 'Maximální délka je %length%',
		'flash_default_language_cannot_be_deactivate' => 'Výchozí jazyk nemůže být deaktivován',
		'header_modules' => 'Moduly',
		'header_editModule' => 'Upavit modul',
		'header_roleAuthorization' => 'Přístupová práva',
		'header_role' => 'Role',
		'header_newRole' => 'Nová role',
		'header_roleEdit' => 'Editace role',
		'header_roleAuthorizationSet' => 'Nastavení přístupových práv',
		'header_setting' => 'Nastavení',
		'header_setting_test_email' => 'Testovací email',
		'column_presenter' => 'Presenter',
		'input_systemName' => 'Systémové jméno',
		'input_presenter' => 'Presenter',
		'input_icon' => 'Ikona',
		'input_parent' => 'Nadřazená položka',
		'flash_moduleNotFound' => 'Modul nebyl nalezen',
		'flash_module_updated' => 'Modul upraven',
		'menu_authorization' => 'Oprávnění',
		'column_privileges' => 'Oprávnění',
		'Nastavit' => 'Nastavit',
		'input_privileges' => 'Oprávnění',
		'send_update' => 'Upravit',
		'send_create' => 'Vytvořit',
		'input_system_name' => 'Systémové jméno',
		'flash_roleAuthorizationSet' => 'Role nastavena.',
		'flash_roleCreated' => 'Role vytvořena.',
		'flash_cannotUpdateSystematicRole' => 'Nelze upravit systémovou roli.',
		'menu_newRole' => 'Nová role',
		'flash_roleNotFound' => 'Role nebyla nalezena.',
		'input_receiver' => 'Příjemce',
		'input_message' => 'Zpráva',
		'send_send' => 'Odeslat',
		'field_general' => 'Obecné',
		'input_shareimage' => 'Obrázek pro sdílení',
		'field_email' => 'Email',
		'input_email_sender' => 'Odesílatel',
		'input_smtp_host' => 'SMTP host',
		'input_smtp_username' => 'SMTP uživatelské jméno',
		'input_smtp_password' => 'SMTP heslo',
		'field_recaptcha' => 'Recaptcha',
		'input_recaptcha_secret_key' => 'Recaptcha tajný klíč',
		'input_recaptcha_site_key' => 'Recaptcha veřejný klíč',
		'flash_email_sended' => 'Email odeslán',
		'flash_setting_updated' => 'Nastavení upraveno',
		'menu_send_test_email' => 'Odeslat testovací email',
		'flash_synchronizeComplete' => 'Synchronizace dokončena',
		'flash_FormDeleted' => 'Formulář smazán',
		'header_enumerationEdit' => 'Editace výčtu',
		'header_enumerationEditItem' => 'Editace položky výčtu',
		'header_newItemForm' => 'Nová položka',
		'header_enumerationNew' => 'Nový výčet',
		'header_enumeration' => 'Výčet',
		'inputType_image' => 'Obrázek',
		'menu_new' => 'Nová položka',
		'column_id' => 'ID',
		'flash_enumerationItemValueUpdated' => 'Hodnota upravena.',
		'flash_enumerationItemDeleted' => 'Položka smazána.',
		'flash_enumerationItemCreated' => 'Položka vytvořena.',
		'menu_newItem' => 'Nová položka',
		'flash_enumerationUpdated' => 'Výčet upraven.',
		'flash_enumerationNotFound' => 'Výčet nebyl nalezen.',
		'flash_enumerationDeleted' => 'Výčet smazán.',
		'flash_enumerationCreated' => 'Výčet vytvořen.',
		'flash_enumerationItemNotFound' => 'Položka nebyla nalezena.',
		'input_logo' => 'Logo',
		'input_logo_small' => 'Logo malé',
		'input_logo_dark' => 'Logo darkmode',
		'input_logo_dark_small' => 'Logo darkmode malé',
		'inputType_editor' => 'HTML editor',
		'column_internalName' => 'Interní název',
		'input_internalName' => 'Interní název',
		'error_typeAlreadyExists' => 'Takový typ už existuje',
		'input_role' => 'Role',
		'header_performance' => 'Performance',
		'flash_performanceSet' => 'Nastavit',
		'flash_performanceCreated' => 'Performance vytvořena',
		'menu_newPerformance' => 'Nová performance',
		'input_position' => 'Pořadí',
		'input_create' => 'Vytvořit',
		'header_editImage' => 'Upravit obrázek',
		'hader_images' => 'Obrázky',
		'input_alt' => 'ALT',
		'input_description' => 'Description',
		'input_author' => 'Autor',
		'flash_imageNotFound' => 'Obrázek nebyl nalezen',
		'flash_imageUpdated' => 'Obrázek upraven',
		'field_images' => 'Obrázky',
		'input_maxImageResolution' => 'Maximální rozlišení obrázku',
		'flash_deleteUnusedImages' => 'Smazat nepouživané obrázky',
		'menu_deleteUnused' => 'Smazat nepoužívané',
		'column_image' => 'Obrázek',
		'column_alt' => 'ALT',
		'column_author' => 'Autor',
		'column_description' => 'Description',
		'column_used' => 'Použito',
		'column_action' => 'Akce',
		'header_languageSetting' => 'Nastavení jazyků',
		'input_radio_languageByUrl' => 'Detekce jazyka z URL',
		'input_radio_languageByHost' => 'Detekce jazyka podle HOST',
		'input_host' => 'Host',
		'flash_languageSettingUpdated' => 'Nastavení jazyků upraveno',
		'menu_setting' => 'Nastavení',
		'menu_front' => 'Frontend',
		'menu_admin' => 'Admin',
		'filter_onlyNotTranslated' => 'Pouze nepřeložené',
		'header_editUser' => 'Editace uživatele',
		'header_changePassword' => 'Změnit heslo',
		'input_set' => 'Nastavit',
		'header_performanceShow' => 'Nastavení performance',
		'performance_typeHtml' => 'HTML',
		'performance_typeUrl' => 'URL',
		'performance_typeFile' => 'Soubor',
		'column_positiion' => 'Pořadí',
		'column_type' => 'Typ',
		'flash_performanceItemDeleted' => 'Položka performance smazána',
		'flash_performanceItemNotFound' => 'Taková položka performance nebyla nalezena',
		'flash_performanceUpdated' => 'Performance upravena',
		'menu_show' => 'Zobrazit',
		'error_internalNameAlreadyExists' => 'Takový interní název už existuje',
		'header_favicons' => 'Favicony',
		'new_favicon' => 'Přidat faviconu',
		'new_import' => 'Nový import',
		'home_googleAnalyticsNotConfigured' => 'Google analytics API nebylo nastaveno',
		'header_homeAccessGraph' => 'Graf přístupů',
		'dasboard_lastChanges' => 'Poslední změny',
		'action_created' => 'Vytvořeno',
		'action_updated' => 'Upraveno',
		'action_deleted' => 'Smazáno',
		'action_imported' => 'Importováno',
		'action_deletedUnused' => 'Smazány nepoužívané',
		'action_changeDefault' => 'Změna výchozího',
		'action_changeActive' => 'Aktivace',
		'action_changePassword' => 'Změna hesla',
		'action_setAuthorization' => 'Nastavení přístupů',
		'action_testedEmail' => 'Odeslán testovací email',
		'action_translated' => 'Přeloženo',
		'action_synchronized' => 'Synchronizovány překlady',
		'action_createdItem' => 'Vytvořena položka',
		'action_updatedItem' => 'Upravena položka',
		'flash_fileNotFound' => 'Soubor nebyl nalezen',
		'flash_faviconImported' => 'Favicony naimportovány',
		'flash_faviconUpdated' => 'Favicona upravena',
		'flash_faviconNotFound' => 'Taková favicona nebyla nalezena',
		'flash_faviconCreated' => 'Favicona vytvořena',
		'menu_import' => 'Import',
		'column_rel' => 'REL',
		'column_sizes' => 'Element sizes',
		'column_content' => 'Element "content"',
		'flash_filesNotFound%files%' => 'Nebyly nalezeny soubory: %files%',
		'input_html' => 'HTML',
		'input_files' => 'Soubory',
		'input_import' => 'Import',
		'input_tag' => 'Tag',
		'input_rel' => 'Rel',
		'input_sizes' => 'Sizes',
		'input_href' => 'Href',
		'input_content' => 'Content',
		'input_image' => 'Image',
		'input_imageToAttribute' => 'Atribut, do kterého se vloží obrázek',
		'column_module' => 'Modul',
		'column_date' => 'Datum',
		'column_user' => 'Uživatel',
		'input_settingGoogleServiceAccount' => 'Nastavení servisního účtu google',
		'input_settingGoogleAnalyticsServiceId' => '"service id" pro Google analytics',
		'performance_positionHead' => 'Head',
		'performance_positionBodyStart' => 'Začátek <body>',
		'performance_positionEnd' => 'Konec <body>',
		'input_edit' => 'Upravit',
		'header_editFavicon' => 'Editace favicony',
		'favicon_importHelp' => 'Import favicon. Je potřeba zadat vygenerované HTML a tomu odpovídající obrázky. Manifest bude upraven automaticky.',
		'field_googleAnalytics' => 'Google Analytics',
		'flash_faviconDeleted' => 'Favicona smazána',
		'type_text' => 'Typ text',
		'type_html' => 'Typ HTML',
		'input_valueText' => 'Text',
		'input_valueHtml' => 'HTML',
		'form_recaptchaBotDetected' => 'Jste robot ?',
		'flash_itemNotFound' => 'Položka nebyla nalezena',
		'flash_itemDeleted' => 'Položka smazána',
		'header_emails' => 'Odeslané emaily',
		'new_email' => 'Nový email',
		'header_emailDetail' => 'Detail emailu',
		'email_created:' => 'Vytvořeno',
		'email_address:' => 'Adresa',
		'email_sender:' => 'Odesílatel',
		'email_subject:' => 'Předmět',
		'email_error:' => 'Chyba',
		'email_text:' => 'Text',
		'action_deletedItem' => 'Smazat položku',
		'action_createdGroup' => 'Vytvořit skupinu',
		'action_updatedGroup' => 'Upravit skupinu',
		'action_deletedGroup' => 'Smazat skupinu',
		'flash_emailDeleted' => 'Email smazán',
		'flash_emailUpdated' => 'Email upraven',
		'flash_emailNotFound' => 'Email nebyl nalezen',
		'flash_emailLogNotFound' => 'Log emailu nebyl nalezen',
		'flash_emailCreated' => 'Email vytvořen',
		'menu_newEmail' => 'Nový email',
		'menu_list' => 'Přehled',
		'column_subject' => 'Předmět',
		'column_created' => 'Vytvořeno',
		'column_address' => 'Adresa',
		'column_error' => 'Chyba',
		'Update' => 'Upravit',
		'Create' => 'Vytvořit',
		'input_subject' => 'Předmět',
		'input_text' => 'Text',
		'input_modifier' => 'Modifikátory',
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
		'input_template' => 'Šablona',
		'header_performanceTranslates' => 'Překlady',
		'menu_translates' => 'Překlady',
		'input_isPerformance' => 'Zobrazovat v performance',
		'no_recent_activity' => 'Žádná poslední aktivita',
		'view_all_activity' => 'Zobrazit veškerou aktivitu',
		'flash_roleIsSystemicCannotEdit' => 'Nemůžete upravovat systémové role',
		'flash_roleIsSystemicCannotDelete' => 'Nemůžete mazat systémové role',
		'flash_roleDeleted' => 'Role smazána',
		'flash_languageIsDefault' => 'Jazyk je výchozí',
		'header_translateNew' => 'Nový klíč',
		'input_key' => 'Klíč',
		'flash_keyCreated' => 'Klíč vytvořen',
		'datagrid.confirmDelete' => 'Potvrzení smazání',
		'datagrid.cancel' => 'Zrušit',
		'datagrid.confirm' => 'ANO smazat',
		'home_googleAnalyticsConfigure' => 'Nastavit Google Analytics',
		'header_log' => 'Log změn',
		'content_fieldTypeDropzone' => 'Dropzone',
		'input_defaultType' => 'Výchozí typ',
		'input_hasContent' => 'Obsahuje obsah',
		'input_enabledCreateForAdmin' => 'Povolit vytváření/mazání pro admina',
		'content_fieldTypeCheckbox' => 'Checkbox',
		'content_fieldTypeTextArea' => 'TextArea',
		'content_emptyName' => 'Nový článek',
		'column_targetId' => 'Ovlivněné ID',
		'input_type_select' => 'Výběr hodnot',
		'flash_userDeleted' => 'Uživatel smazán',
		'flash_userCannotByDeleted' => 'Tento uživatel nemůže být smazán',
		'home_googleAnalyticsError' => 'Chyba v získávání dat z Google Analytics',
		'header_analytics' => 'Google Analytics',
		'flash_setting_analyticsUpdated' => 'Google Analytics nastaveno',
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
