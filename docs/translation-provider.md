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
  Pole jedné entity navíc mohou skončit v různých dávkách (dávka má nejvýš
  40 textů), takže `save()` nesmí počítat s tím, že dostane všechna pole naráz.
- **`save()` běží bez přihlášeného uživatele.** Volá ho callback DropCore, což je
  anonymní HTTP požadavek na `Admin:LanguageCallback:translate`. `save()` proto
  nesmí potřebovat `Nette\Security\User` ani nic, co na něm závisí (např.
  `LogFacade`, který vyžaduje `user_id`). Výjimku ze `save()` callback jen
  zaloguje a pokračuje dalším textem, takže se taková chyba projeví jen
  chybějícím překladem. Pozor: **simulace DropCore tohle neodhalí**, protože
  dávky „vrací“ v požadavku lišty s průběhem, kde přihlášený uživatel je.
- **Cache si `save()` maže sám.** Callback maže jen cache jádra a modulů inCore
  (UI texty, číselníky, formuláře, obsah). Vlastní cache cílové aplikace,
  ve které je přeložená hodnota, musí `save()` invalidovat sám.
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

## Čitelný název zdroje (`LabeledTranslationProvider`)

Každý překlad odeslaný do DropCore se zobrazí v liště s průběhem vpravo dole
v administraci (viz „Lišta s průběhem překladu“ níže). Aby v ní zdroj nebyl
označený technickým `systemName`, implementuje navíc volitelné rozhraní
`App\Component\Translation\LabeledTranslationProvider`:

```php
public function getLabel(): string
{
    return 'translationSource_mujZdroj'; // překladový klíč, nebo rovnou text
}
```

Vrácená hodnota projde přes `Translator`, takže může být překladový klíč
(doporučeno) i hotový text. Zdroj bez rozhraní se v liště označí svým
`systemName`.

## Názvy položek v metadatech (`NamedTranslationProvider`)

Každá dávka odeslaná do DropCore nese česká metadata, aby v jeho logu bylo
vidět, co se překládalo (`LanguageFacade::buildMetadata()`). DropCore je nijak
nezpracovává, čte je člověk, proto v nich nejsou ID ani počty textů — jen
název zdroje a názvy položek, např. `"obsah": {"články": ["Jak na to", "Novinky"]}`.
Názvy dodá zdroj přes volitelné rozhraní
`App\Component\Translation\NamedTranslationProvider`:

```php
/** @return array<int, string> id => název ve výchozím jazyce */
public function getItemNames(array $ids): array
{
    return $this->mujModel->getTable()->where('id', $ids)->fetchPairs('id', 'name');
}
```

`$ids` jsou `TranslationItem::$id` položek v dávce. Zdroj bez rozhraní je
v metadatech jen svým názvem s prázdným seznamem položek. Slovník UI textů (`translate`)
posílá místo názvů klíče.

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
dělat nemusíte. Cílová aplikace založená ze starší verze `incore-app` ho
mít nemusí – bez něj se provider nezaregistruje a hromadný překlad ho
**bez jakékoli chyby** přeskočí. Pokud provider přidáváte do samostatného
balíčku, přidejte tam analogickou sekci `search` s vaším adresářem místo
`%appDir%`.)

Rychlá kontrola, že je provider zaregistrovaný (v kontejneru aplikace):

```php
$registry = $container->getByType(App\Component\Translation\TranslationProviderRegistry::class);
var_dump($registry->get('mujZdroj') !== null);
```

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

## Překlad jednotlivých položek z presenteru

Kromě hromadného překladu celého jazyka umí `LanguageFacade` přeložit i
vybrané položky zdroje — typicky tlačítko „Přeložit“ u detailu konkrétní
entity:

- **`translateProviderItemsToAllLanguages(string $systemName, array $ids, ?string $jobLabel = null)`**
  — přeloží položky do všech jazyků k překladu. V liště s průběhem je to
  jedna společná úloha. Pro tlačítka „Přeložit“ u detailu je to správná volba.
- **`translateProviderItems(string $systemName, array $ids, ActiveRow $language, ?string $jobLabel = null)`**
  — přeloží položky do jednoho jazyka (např. hromadná akce s výběrem jazyka).
- **`translateProviderItem(...)`** — totéž pro jedinou položku.

Metody si najdou provider podle `systemName`, zavolají jeho `collect()` s
omezením na daná `$id` a uloží výsledek k překladu — stejnou cestou (a se
stejnými výjimkami `BasicAuthNotSetException` a `TranslateApiException` – ta
jen při chybějícím nastavení DropCore), jako hromadný překlad. Vracejí ID dávek
uložených v `language_translate` – do DropCore je postupně odešle lišta
s průběhem. Reálné použití v jádru — tlačítko „Přeložit“ u slovníku UI textů,
`incore-core/src/app/UI/Admin/Translate/TranslatePresenter.php`:

```php
public function actionTranslate(int $id): void
{
    $this->exist($id);
    try {
        $this->languageFacade->translateProviderItemsToAllLanguages(
            'translate',
            [$this->translate->id],
            $this->translator->translate('translationJob_translate%key%', ['key' => $this->translate->key]),
        );
    } catch (BasicAuthNotSetException $e) {
        $this->flashMessage($this->translator->translate('flash_basicAuthNotSet'), 'error');
        $this->redirect('default');
    } catch (TranslateApiException $e) {
        $this->flashMessage($this->translator->translate('flash_translateApiError'), 'error');
        $this->redirect('default');
    }
    $this->flashMessage($this->translator->translate('flash_sendToTranslate'));
    $this->redirect('default');
}
```

## Lišta s průběhem překladu

Každé volání `LanguageFacade`, které spouští překlad (`translate()`,
`translatePerformancesContent()`, `translateProviderItem(s)()`,
`translateProviderItemsToAllLanguages()`), texty do DropCore **neodesílá** –
rozdělí je do dávek, uloží do `language_translate` (bez `drop_core_id`) a
založí úlohu v liště s průběhem (`App\Component\Translation\TranslationJobFacade`).
Presenter nemusí nic dalšího dělat – stačí případně předat vlastní popisek
`$jobLabel`. Bez něj se použije „Překlad: {název zdroje}“ (resp. „… → {jazyk}“
u jednoho jazyka), kde název zdroje pochází z `LabeledTranslationProvider::getLabel()`.

Lišta pak dávky po jedné odesílá (`TranslationJob:send`, vyplní `drop_core_id`)
a zobrazuje „Odesílání X/N“, potom „Překládání X/N“. Úloha je hotová, když
všechny dávky mají vyplněný čas `finished` – ten nastaví callback DropCore.
Odesílání pokračuje na libovolné stránce administrace; zavřením prohlížeče se
nic neztratí. Dvě otevřené záložky stejnou dávku neodešlou dvakrát (dávka se
při odesílání zamkne na 2 minuty).

Když odeslání selže (nedostatek kreditů, chyba API), úloha se zastaví, chyba se
uloží k dávce (`language_translate.error`, vidět i v logu překladů) a lišta
nabídne „Zkusit znovu“ nebo „Zrušit“ (smaže neodeslané dávky). Presenter se o
tyto chyby nestará – hned při spuštění se hlásí jen chybějící nastavení
DropCore (`TranslateApiException`) a basic auth (`BasicAuthNotSetException`).

Úlohy patří přihlášenému uživateli a nedoběhlá úloha vyprší po 1 dni.

Lokálně (např. `incore.local`) callback z DropCore nedorazí, protože adresa
není z internetu dostupná – lišta tam u skutečného překladu zůstane stát
na „Překládání“.
