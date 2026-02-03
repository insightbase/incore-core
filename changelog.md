# Changelog

## 2026-02-03
### Opraveno (Fixed)
- core - statické stránky - opravena editace SEO výchozího jazyka

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
