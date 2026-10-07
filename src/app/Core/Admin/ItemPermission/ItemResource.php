<?php

namespace App\Core\Admin\ItemPermission;

/**
 * Konkrétní položka modulu jako ACL resource. Dědí pravidla modulu (resource id = system_name modulu),
 * aserce omezené role podle ní pozná, o kterou položku jde.
 */
final class ItemResource implements \Nette\Security\Resource
{
    public function __construct(
        public readonly string $module,
        public readonly int $itemId,
    ) {}

    public function getResourceId(): string
    {
        return $this->module;
    }
}
