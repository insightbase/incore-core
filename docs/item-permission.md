# Omezení role na položky modulu (ItemPermissionProvider)

Roli jde u modulu omezit jen na vybrané položky (např. jen na kontaktní
formulář ID 1). Omezení platí pro všechna privilegia, která role v modulu má,
a super admina se netýká. Modul ho podporuje, když:

1. **Implementuje `App\Core\Admin\ItemPermission\ItemPermissionProvider`**
   (`getModuleSystemName()` = `module.system_name`, `getItems()` = `id => popisek`).
   V jádru a modulech inCore se provider najde sám (`search: implements:` v
   `core.neon`). Cílová aplikace potřebuje ve svém `config/services.neon` v
   `search: implements:` řádek `- App\Core\Admin\ItemPermission\ItemPermissionProvider`.
   Pak se v administraci rolí u modulu objeví „Omezit na vybrané položky“.
2. **Vynucuje omezení přes `ItemPermissionFacade`:**
   - seznam: `$facade->filterSelection($selection, MODULE)` (sloupec jde změnit třetím parametrem),
   - akce nad položkou: `$facade->checkItem(MODULE, $id, 'edit')` ještě před načtením a výstupem; vyhodí 403,
   - po vytvoření položky: `$facade->grantToCurrentRole(MODULE, $newId)`,
   - po smazání položky: `$facade->removeItem(MODULE, $id)`.

Vlastní dotazy na ACL nad položkou vždy přes objekt
`new ItemResource(MODULE, $id)`, nikdy stringem modulu — string je dotaz na
úrovni modulu a omezení ho pustí (menu, seznam, nová položka).
Referenční implementace: `incore-forms`, `ContactFormItemPermissionProvider`.
