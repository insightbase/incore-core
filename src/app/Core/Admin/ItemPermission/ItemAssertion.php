<?php

namespace App\Core\Admin\ItemPermission;

use Nette\Security\Permission;

/**
 * Aserce pravidla omezené role: dotaz na úrovni modulu (string) pustí, dotaz na položku
 * (ItemResource) jen pro povolená ID. Nette předá dotazovaný objekt jen přes getQueriedResource().
 */
final readonly class ItemAssertion
{
    /**
     * @param list<int> $allowedIds
     */
    public function __construct(
        private array $allowedIds,
    ) {}

    public function __invoke(Permission $acl): bool
    {
        $resource = $acl->getQueriedResource();
        if (!$resource instanceof ItemResource) {
            return true;
        }

        return in_array($resource->itemId, $this->allowedIds, true);
    }
}
