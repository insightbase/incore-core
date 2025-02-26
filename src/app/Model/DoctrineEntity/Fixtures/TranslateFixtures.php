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
		'header_login' => 'Přihlášení',
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
		'%itemFrom%-%itemTo% z %count%' => null,
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
		'Editace uživatele' => null, // TODO: add translation
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
		'Nastavit' => 'Nastavit', // TODO: add translation
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
		'input_favicon' => 'Favicon',
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
		'input_logo_dark_small' => 'Logo darkmode malé'
	];


	public function load(\Doctrine\Persistence\ObjectManager $manager): void
	{
		$language = $this->getReference(LanguageFixtures::LANG_CS, Language::class);
        foreach($this->translates as $key => $value){
            $translate = $manager->getRepository(Translate::class)->findOneBy(['key' => $key]);
            if($translate === null) {
                $translate = new Translate();
                $translate->setKey($key);
                $manager->persist($translate);
            }
            if($value !== null){
                $translateLanguage = new TranslateLanguage();
                $translateLanguage->setLanguage($language);
                $translateLanguage->setTranslate($translate);
                $translateLanguage->setValue($value);
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
