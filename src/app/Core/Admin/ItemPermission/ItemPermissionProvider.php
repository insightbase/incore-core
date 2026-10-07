<?php

namespace App\Core\Admin\ItemPermission;

/**
 * Modul, jehož položky jde roli povolit jednotlivě. Implementace se najde sama
 * (autodiscovery v core.neon), v administraci rolí se pak u modulu objeví výběr položek.
 */
interface ItemPermissionProvider
{
    /** system_name modulu, ke kterému provider patří (např. 'forms') */
    public function getModuleSystemName(): string;

    /** @return array<int, string> id položky => popisek do UI */
    public function getItems(): array;
}
