<?php

namespace App\Core\Admin\ItemPermission;

use App\Model\Admin\Module;
use App\Model\Admin\PermissionItem;
use App\Model\Admin\PermissionItemRestriction;
use App\Model\Admin\Role;
use App\Model\Enum\RoleEnum;
use Nette\Application\ForbiddenRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Security\User;

/**
 * Jediné místo, přes které moduly pracují s omezením role na položky.
 */
final class ItemPermissionFacade
{
    private ?ItemRestrictionMap $map = null;

    public function __construct(
        private readonly User $user,
        private readonly Role $roleModel,
        private readonly Module $moduleModel,
        private readonly PermissionItemRestriction $permissionItemRestrictionModel,
        private readonly PermissionItem $permissionItemModel,
    ) {}

    /** true, pokud je role přihlášeného uživatele u modulu omezená (super admin nikdy) */
    public function isRestricted(string $module): bool
    {
        return $this->getAllowedIds($module) !== null;
    }

    /**
     * @return ?list<int> null = bez omezení, jinak povolená ID (může být prázdné)
     */
    public function getAllowedIds(string $module): ?array
    {
        $role = $this->getCurrentRole();
        if ($role === null) {
            return null;
        }

        return $this->getMap()->getAllowedIds($role, $module);
    }

    /**
     * @template T of ActiveRow
     * @param Selection<T> $selection
     * @return Selection<T>
     */
    public function filterSelection(Selection $selection, string $module, string $column = 'id'): Selection
    {
        return ItemSelectionFilter::apply($selection, $this->getAllowedIds($module), $column);
    }

    /**
     * @throws ForbiddenRequestException
     */
    public function checkItem(string $module, int $itemId, string $privilege): void
    {
        if (!$this->user->isAllowed(new ItemResource($module, $itemId), $privilege)) {
            throw new ForbiddenRequestException();
        }
    }

    /** Nově vytvořenou položku přidá omezené roli přihlášeného uživatele mezi povolené. */
    public function grantToCurrentRole(string $module, int $itemId): void
    {
        $roleName = $this->getCurrentRole();
        if ($roleName === null || !$this->isRestricted($module)) {
            return;
        }
        $role = $this->roleModel->getBySystemName($roleName);
        $moduleRow = $this->moduleModel->getBySystemName($module);
        if ($role === null || $moduleRow === null) {
            return;
        }

        $this->permissionItemModel->insertIfMissing($role->id, $moduleRow->id, $itemId);
        $this->map = null;
    }

    /** Úklid při smazání položky: odebere ji všem rolím. */
    public function removeItem(string $module, int $itemId): void
    {
        $moduleRow = $this->moduleModel->getBySystemName($module);
        if ($moduleRow === null) {
            return;
        }

        $this->permissionItemModel->deleteByModuleAndItem($moduleRow->id, $itemId);
        $this->map = null;
    }

    /** system_name role přihlášeného uživatele; null pro nepřihlášeného a super admina (neomezují se) */
    private function getCurrentRole(): ?string
    {
        if (!$this->user->isLoggedIn()) {
            return null;
        }
        $roles = $this->user->getRoles();
        if (in_array(RoleEnum::SUPER_ADMIN->value, $roles, true)) {
            return null;
        }
        // uživatel má právě jednu roli (Authenticator)
        $role = $roles[0] ?? null;

        return is_string($role) ? $role : null;
    }

    private function getMap(): ItemRestrictionMap
    {
        return $this->map ??= ItemRestrictionMap::fromRows(
            $this->permissionItemRestrictionModel->getAllForAcl(),
            $this->permissionItemModel->getAllForAcl(),
        );
    }
}
