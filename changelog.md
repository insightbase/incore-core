# Changelog
## 2026-04-21
### Přidáno (Added)
- users - výpis uživatelů - nový sloupec `Role` (zobrazuje název role přes vazbu `role`, řaditelný podle `role.name`)

## 2026-04-20
### Opraveno (Fixed)
- core - jazyky - `actionDelete` nyní blokuje smazání výchozího jazyka (flash `flash_languageIsDefault`) a v DataGridu je položka Smazat skryta pro výchozí jazyk; dříve smazání shodilo `RouterFactory` na `$default->url` na null
- core - nastavení jazyka - `LanguageSettingFormData::$type` převeden na nullable a facade přeskočí update při null, aby prázdná hodnota neshodila mapování formuláře na TypeError
- core - úprava obrázku - `EditFormData::$image_id` převeden na nullable a `ImageFacade::edit()` vyhazuje `ImageNotFoundException` při null, prázdné image_id už neshodí render
- core - postranní menu - `MainMenuItem::isValidLink()` hlídá formát presenter jména z DB a `@layout.latte` skryje odkazy s neplatným presenterem místo vyhození výjimky o alfanumerickém názvu

## 2026-04-15
### Přidáno (Added)
- forms - u kontaktního formuláře lze u typu select, radiolist a checkboxlist zadat hodnoty (středníkem oddělené)
### Opraveno (Fixed)
- forms - drag&drop pořadí řádků v editaci formuláře
- forms - přidávání a mazání řádků při editaci formuláře
- core - select (Choices) sjednocen vzhledem s inputem; rozbalený dropdown se zobrazuje nad dalším blokem

## 2026-04-13
### Opraveno (Fixed)
- core - dropzone - chunkSize předáván Dropzone jako number (parseInt), string způsoboval špatné rozdělení chunků a chybu UPLOAD_ERR_INI_SIZE při větších souborech
- core - dropzone - data-chunksize počítán jako 90 % z min(upload_max_filesize, post_max_size, 1 MB), aby se vešel i do defaultního nginx client_max_body_size (1 MB)
### Přidáno (Added)
- core - nastavení - přidáno pole `max_chunk_size` (v KB) pro ruční override velikosti chunku v Dropzone; při nevyplnění se použije automatický výpočet

## 2026-03-10
### Opraveno (Fixed)
- content - detail - editorJS se nemusí upravovat v modal okně
### Přidáno (Added)
- update db script

## 2026-03-03
### Opraveno (Fixed)
- core - preview - opraveno ukončení presenteru
### Přidáno (Added)
- core - translates - změna klíčů překladů - jen v dev modu

## 2026-02-26
### Přidáno (Added)
- core - logovani chyb na discord

## 2026-02-25
### Opraveno (Fixed)
- core - entity - sloupce které mají v sobě Enum jsou typu ENUM

## 2026-02-24
### Opraveno (Fixed)
- core - layout - oprava generování menu - bere se module předaný do MainMenuItemFactory

## 2026-02-20
### Přidáno (Added)
- core - image - přidána možnost rovnou generovat preview a posílat url na thumb
- core - faviconControl - nastaveno generování náhledů hned

## 2026-02-17
### Přidáno (Added)
- core - entityGenerator - přidána možnost nastavit namespace/dir location pro entitu
- core - možnost řídit zobrazování modulů

## 2026-02-16
### Přidáno (Added)
- form - možnost nastavit příjemce pro každý formulář zvlášť

## 2026-02-13
### Přidáno (Added)
- core - layout - možnost přidat blok za H1 (h1AfterAfter)

## 2026-02-12
### Přidáno (Added)
- datagrid - možnost nastavit callback pro zobrazování sloupečku
- datagrid - možnost nastavit beforeRender callbacky u sloupečku

## 2026-02-06
### Přidáno (Added)
- core - překlady - ruční přidání klíče - možnost nastavit zdroj

## 2026-02-05
### Přidáno (Added)
- editorJs - přidána možnost vytvořit odkaz na soubor
### Opraveno (Fixed)
- statické stránky - přidáno truncate do menu
- extrakce prekladu
- checkbox když není nepovinný

## 2026-02-04
### Opraveno (Fixed)
- datagrid - oprava filtru

## 2026-02-03
### Opraveno (Fixed)
- core - statické stránky - opravena editace SEO výchozího jazyka
### Přidáno (Added)
- core - dropzone - možnost omezit typy souborů
- core - nastavení -přidány helpy k logům, hlavní logo může být jen obrázek (nikoliv svg)

## 2026-04-08
### Opraveno (Fixed)
- enumeration - oprava invalidace cache při smazání položky z číselníku

## 2026-02-02
### Přidáno (Added)
- lokalizace jazyků
- modul Statické stránky
### Opraveno (Fixed)
- core - obrázky - opraven problém, kdy se obrázky generují rovnou při loadu stránky. Předěláno na endpoint

## 2026-01-19
### Opraveno (Fixed)
- generování .json pro favicon

## 2026-01-19
### Přidáno (Added)
- n:nonce pro používání CSP

## 2026-01-19
### Opraveno (Fixed)
- core - AI preklady quickfix

## 2026-01-19
### Opraveno (Fixed)
- core - form - u tabů opraven okraj pokud je zanořený a nemá toggle
- core - front trait - definice promennych do sablon presunto do onStartup, aby si to mohl presenter prepsat
- core - imageControl - DI property jsou protected, pokud se dědí potomek aby byly dostupné
- core - imageControl - kvalita 92

## 2026-01-15
### Upraveno (Updated)
- datagrid - nastavena fixní šířka pro sloupeček actions
### Přidáno (Added)
- datagrid - customMenu - přidán title

## 2026-01-15
### Přidáno (Added)
- core - možnost přepnout layout do verze kde je H1 až ve formu (tlačítka vravo nahoře)
### Opraveno (Fixed)
- core - front presenter trait - ošetření když není lang inicializovaný (ErrorPresenter)

## 2026-01-13
### Opraveno (Fixed)
- core - nastavena timezone

## 2026-01-07
### Opraveno (Fixed)
- Core - form - při renderu se do buttonů pod formulář vkládají jen submit button, nikoliv klasické buttony

## 2026-01-02
### Opraveno (Fixed)
- Datagrid - fix - pokud ma column relaci a je nulová hodnota

## 2025-12-19
### Přidáno (Added)
- core - modul se dá propojit s číselníkem. Lze takto upravovat levé menu

## 2025-12-18
### Opraveno (Fixed)
- Core - input slug - uprava chovani pokud neni navazan na input

## 2025-12-17
### Opraveno (Fixed)
- Datagrid - opraven filtr select
- Nastavení - přidán email, na který chodí formy

## 2025-12-16
### Opraveno (Fixed)
- Datagrid - opraven bug, pokud je sloupeček relace

## 2025-12-04
### Opraveno (Fixed)
- datagrid - pokud je datovy typ "time" v DB, vraci spravny cas

## 2025-12-12
### Přidáno (Added)
- core - formuláře - přidána možnost kopírovat obsah inputu
- content - tagy - přidán slug a SEO

## 2025-12-04
### Opraveno (Fixed)
- Formuláře - opraveno řazení podle sloupečků
### Přidáno (Added)
- datagrid - sloupečky mají možnost změnit styl řazení

## 2025-12-09
### Přidáno (Added)
- endpoint pro upravení DB, presun logiky do facade

## 2025-12-04
### Opraveno (Fixed)
- EditorJS - ovládací prvky vždy vlevo

## 2025-12-03
### Přidáno (Added)
- Formuláře - možnost nastavit toggle pro skupiny

## 2025-12-01
### Přidáno (Added)
- Nastavení - přidána možnost vložit placeholder
### Opraveno (Fixed)
- generování DB pro novější verzi symfony
- datagrid - sloupecek datum - opraveno pokud je nulovy

## 2025-12-01
### Přidáno (Added)
- Nastavení - přidána možnost nastavit title pro subpage. Na FE se potom přidává za title na subpages

## 2025-11-28
### Přidáno (Added)
- Formuláře - přidán radioList

## 2025-11-26
### Opraveno (Fixed)
- Překldy - opraven inline edit EditorJs, pokud obsahuje speciální znaky, které by mohly rozbít URL

## 2025-11-25
### Opraveno (Fixed)
- Dropzone - opraveno odstranění obrázku, modal okno nastavení obrázku lze otevřít už při uploadu, opraven upload drag&drop více obrázků když není multi


*EXAMPLE BELOW:*
## 2025-11-20
### Přidáno (Added)
- Modul 1
### Změněno (Changed)
- Optimalizace obrázků.
### Opraveno (Fixed)
- Chybné zobrazení tabulky v adminu.
