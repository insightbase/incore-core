# Zdroje textů pro automatický překlad (TranslationProvider)

Hromadný překlad jazyka v administraci (Jazyky → Přeložit) posílá do
externí služby DropCore texty ze všech míst aplikace, která se do překladu
zapojila. Každé takové místo je samostatná třída implementující rozhraní
`App\Component\Translation\TranslationProvider`. Jádro i moduly inCore
používají stejný mechanismus — a autoři cílových aplikací si jím mohou
zapojit i vlastní texty (vlastní entity, vlastní formuláře apod.), aniž by
zasahovali do jádra.

V jádru a v modulech inCore se provider sám zaregistruje přes autodiscovery
(viz níže) — nikde ho není potřeba ručně přidávat do seznamu. V cílové
aplikaci to platí, jen pokud je autodiscovery pro její vlastní balíček
zapnutá — jinak provider nikam nepatří a hromadný překlad ho beze
zjevné chyby prostě přeskočí (viz sekce „Registrace v `config/services.neon`“
níže).

## Kam provider umístit

Umístění rozhoduje o tom, kde ho autodiscovery Nette DI najde:

- **Jádro** (`incore-core`) používá `App\Component\Translation\Provider\`
  (podadresář `Provider`).
- **Moduly** (`incore-content`, `incore-enumeration`, `incore-forms`, …) a
  **cílové aplikace** používají `App\Component\Translation\` přímo ve
  vlastním balíčku (bez podadresáře `Provider`) — tedy např.
  `App\Component\Translation\MujZdrojTranslationProvider` v `incore-app`
  nebo ve vlastním balíčku cílové aplikace.

Obojí umístění autodiscovery najde stejně — jde jen o zavedenou konvenci
(jádro odlišuje `Provider` podadresářem, protože v `App\Component\Translation\`
má i sdílené třídy jako `TranslationItem`, `TranslationKey` a
`TranslationProviderRegistry`; moduly a cílové aplikace žádné takové sdílené
třídy ve vlastním balíčku nemají, takže podadresář nepotřebují).

## Volitelné moduly

Pokud zdroj textů patří k volitelnému modulu (např. obsah, číselníky,
formuláře), musí `collect()` i `save()` nejdřív ověřit, že je modul
nainstalovaný/zapnutý. Když není, `collect()` vrátí **prázdné pole** a
`save()` se rovnou vrátí bez uložení — hromadný překlad tak funguje i
v instalaci, kde je modul vypnutý, a nikde nespadne na chybějící tabulce.

## Hodnota: řetězec, nebo pole

`TranslationItem::$value` i parametr `$value` v `save()` mají typ
`string|array<string, mixed>`. Řetězec stačí pro běžný text. Pole se
používá u obsahu ve formátu JSON/EditorJs — `collect()` uloženou JSON
hodnotu **dekóduje** (`Json::decode(..., true)`) a vrátí jako pole, `save()`
přijaté pole před uložením zase **zakóduje** (`Json::encode()`) zpátky na
řetězec. DropCore samo o sobě pole i vnořené texty umí přeložit, takže se
tímto zajistí, že se překládají jen textové hodnoty uvnitř struktury, ne
technické klíče JSON.

## Ukázková třída

Nejjednodušší provider v jádru je `EmailTranslationProvider` — překládá
předmět a text e-mailových šablon. Slouží jako vzor:

```php
<?php

namespace App\Component\Translation\Provider;

use App\Component\Translation\TranslationItem;
use App\Component\Translation\TranslationProvider;
use App\Model\Admin\Email;
use App\Model\Admin\EmailLanguage;
use App\Model\Entity\LanguageEntity;
use Nette\Database\Table\ActiveRow;

/**
 * Zdroj překladu předmětu a textu e-mailových šablon.
 */
final readonly class EmailTranslationProvider implements TranslationProvider
{
    private const array FIELDS = ['subject', 'text'];

    public function __construct(
        private Email $emailModel,
        private EmailLanguage $emailLanguageModel,
    ) {}

    public function getSystemName(): string
    {
        return 'email';
    }

    /**
     * @param LanguageEntity $language
     * @return TranslationItem[]
     */
    public function collect(ActiveRow $language, ?int $id = null): array
    {
        if ($id === null) {
            $emails = $this->emailModel->getTable();
        } else {
            $row = $this->emailModel->get($id);
            $emails = $row === null ? [] : [$row];
        }

        $items = [];
        foreach ($emails as $email) {
            foreach (self::FIELDS as $field) {
                if ($email->{$field} !== null && $email->{$field} !== '') {
                    $items[] = new TranslationItem($email->id, $field, $email->{$field});
                }
            }
        }

        return $items;
    }

    /**
     * @param string|array<string, mixed> $value
     * @param LanguageEntity $language
     */
    public function save(int $id, string $field, string|array $value, ActiveRow $language): void
    {
        if (!in_array($field, self::FIELDS, true) || is_array($value) || $this->emailModel->get($id) === null) {
            return;
        }

        $emailLanguage = $this->emailLanguageModel->getByEmailIdAndLanguage($id, $language);
        if ($emailLanguage === null) {
            $this->emailLanguageModel->insert([
                'email_id' => $id,
                'language_id' => $language->id,
                $field => $value,
            ]);
        } else {
            $emailLanguage->update([$field => $value]);
        }
    }
}
```

Tři metody rozhraní:

- **`getSystemName()`** — vrací unikátní jméno zdroje (pravidla viz níže).
- **`collect(ActiveRow $language, ?int $id = null)`** — vrátí pole
  `TranslationItem[]` s texty ve **výchozím** jazyce, které se mají přeložit
  do jazyka `$language`. Když je zadané `$id`, omezí sběr na jedinou entitu
  (viz `translateProviderItem()` níže) — jinak sbírá všechny.
- **`save(int $id, string $field, string|array $value, ActiveRow $language)`**
  — uloží přeloženou hodnotu `$value` pole `$field` entity `$id` pro jazyk
  `$language`. Volá ji `LanguageFacade::processDropCoreCallback()`, když
  DropCore vrátí hotový překlad. Neznámé `$field` nebo neexistující `$id`
  se má tiše přeskočit (`return`), stejně jako typ hodnoty, který provider
  neumí uložit (viz `is_array($value)` výše — e-maily pole neukládají).

## Vlastnosti kontraktu, které nejsou vidět na první pohled

- **`collect()` smí zapisovat do databáze.** Typicky jen čte, ale není to
  podmínkou — `ContentTranslationProvider::collect()` například při sběru
  zakládá chybějící jazykové řádky obsahu (`contentValue`, `contentValueItem`
  a kopie galerií), protože bez nich by neměl kam později uložit přeloženou
  hodnotu. Autor cizího providera by proto neměl spoléhat na to, že zavolat
  `collect()` je bezpečné „jen si to přečíst“.
- **`save()` může být pro jednu entitu v jedné dávce zavoláno vícekrát** —
  jednou pro každé přeložené pole. `BlogTranslationProvider::save()` na tom
  přímo staví: při každém volání načte aktuální jazykovou mutaci, přepíše
  jen pole podle `$field` a celý obsah uloží zpátky, takže se postupná
  volání pro `name`, `slug` i jednotlivé položky JSON obsahu skládají do
  jednoho výsledku, aniž by jedno volání přepsalo výsledek druhého.
- **`$id` v `collect()` a v `save()` nemusí označovat tutéž entitu.** U
  `ContentTranslationProvider` `collect()` parametr `$id` filtruje podle
  `content_id` (obsahu jako celku), ale `TranslationItem::$id`, který se
  vrátí a později přijde do `save()`, je id konkrétní hodnoty (`content_value`
  položky nebo pole obsahu) — ne id obsahu, kterým se sběr omezoval.

## Posílat jen nepřeložené texty (`TranslatedItemsProvider`)

Hromadný překlad jazyka (Jazyky → Přeložit) posílá jen texty, které v cílovém
jazyce ještě přeložené nejsou. Aby to zdroj uměl, implementuje navíc
volitelné rozhraní `App\Component\Translation\TranslatedItemsProvider`
s jedinou metodou:

```php
/** @return array<int, list<string>> id položky => přeložená pole */
public function getTranslatedItems(ActiveRow $language): array;
```

Vrací položky (`TranslationItem::$id` a `$field`), které už v jazyce
`$language` mají překlad — ty se do dávky nezařadí. Metoda se volá až po
`collect()`, takže může počítat i s jazykovými řádky, které `collect()`
založil. Kde se jazyková verze zakládá jako kopie výchozího jazyka (obsah,
blog), bere se hodnota jako přeložená, až když se od výchozího jazyka liší.

Zdroj bez tohoto rozhraní funguje dál, jen při hromadném překladu posílá
vždy všechno. Překlad jediné položky (`translateProviderItem()`) posílá
vždy všechno bez ohledu na rozhraní — je to vědomé „přelož znovu“.

## Registrace v `config/services.neon`

Nette DI najde třídy implementující `TranslationProvider` jen tam, kde je
o to explicitně požádaná sekcí `search: implements:`. V jádru je to už
nastavené (`incore-core/src/config/core.neon`), takže moduly a cílové
aplikace, jejichž kód leží pod `%vendorDir%/incore/...`, autodiscovery
najde automaticky. Vlastní balíček cílové aplikace ale takhle pokrytý není
— proto je potřeba do jeho vlastního `config/services.neon` přidat řádek
do `search: implements:`:

```neon
search:
    - in: '%appDir%'
      implements:
          - App\Component\Translation\TranslationProvider
```

(Přesně takhle to má nastavené `incore-app/config/services.neon` — pokud
provider přidáváte přímo do `incore-app`, řádek už tam je a nic dalšího
dělat nemusíte. Pokud provider přidáváte do samostatného balíčku, přidejte
tam analogickou sekci `search` s vaším adresářem místo `%appDir%`.)

## Pravidla pro `systemName`

- Musí odpovídat vzoru `^[a-z][a-zA-Z0-9]*$` (malé první písmeno,
  bez podtržítek a pomlček, bez diakritiky).
- Musí být **unikátní** napříč celou aplikací — když dva providery vrátí
  stejné jméno, `TranslationProviderRegistry` při prvním použití vyhodí
  `DuplicateSystemNameException`.

`systemName` je součástí klíče `systemName:id:pole`, kterým se položka
posílá do DropCore a podle kterého se po překladu pozná, kam uloženou
hodnotu vrátit (`App\Component\Translation\TranslationKey`).

## Pravidla pro pole (`$field`)

- Název pole **nesmí obsahovat dvojtečku** — je to oddělovač v klíči
  `systemName:id:pole`, jinak by se klíč po překladu nedal jednoznačně
  rozložit zpátky.
- Hodnota pole je `string`, nebo `array<string, mixed>` u obsahu ve formátu
  JSON/EditorJs (viz sekce výše) — jiný typ (např. `int`, `bool`) provider
  nesmí vracet ani přijímat.

## Překlad jediné položky z presenteru

Kromě hromadného překladu celého jazyka umí `LanguageFacade` přeložit i
jedinou položku zdroje — typicky tlačítko „Přeložit“ u detailu konkrétní
entity. Presenter zavolá `translateProviderItem()` se jménem zdroje,
id entity a cílovým jazykem:

```php
$this->languageFacade->translateProviderItem('mujZdroj', $id, $language);
```

Metoda si najde provider podle `systemName`, zavolá jeho `collect()` s
omezením na dané `$id` a odešle výsledek k překladu — stejnou cestou
(a se stejnými výjimkami `BasicAuthNotSetException`,
`NotEnoughCreditsException`, `TranslateApiException`), jako hromadný
překlad. Reálné použití v jádru — tlačítko „Přeložit“ u slovníku UI textů,
`incore-core/src/app/UI/Admin/Translate/TranslatePresenter.php`:

```php
public function actionTranslate(int $id): void
{
    $this->exist($id);
    try {
        foreach ($this->languageModel->getToTranslateNotDefault() as $language) {
            $this->languageFacade->translateProviderItem('translate', $this->translate->id, $language);
        }
    } catch (BasicAuthNotSetException $e) {
        $this->flashMessage($this->translator->translate('flash_basicAuthNotSet'), 'error');
        $this->redirect('default');
    } catch (NotEnoughCreditsException $e) {
        $this->flashMessage($this->translator->translate('flash_notEnoughCredits'), 'error');
        $this->redirect($this->getUser()->isAllowed('credit', 'default') ? 'Credit:default' : 'default');
    } catch (TranslateApiException $e) {
        $this->flashMessage($this->translator->translate('flash_translateApiError'), 'error');
        $this->redirect('default');
    }
    $this->flashMessage($this->translator->translate('flash_sendToTranslate'));
    $this->redirect('default');
}
```
