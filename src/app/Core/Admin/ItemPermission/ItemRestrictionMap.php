<?php

namespace App\Core\Admin\ItemPermission;

/**
 * Omezení rolí na položky modulů: role => modul => povolená ID.
 * Omezení určuje příznak (permission_item_restriction), ne existence položek.
 */
final readonly class ItemRestrictionMap
{
    /**
     * @param array<string, array<string, list<int>>> $allowed
     */
    private function __construct(
        private array $allowed,
    ) {}

    /**
     * @param list<array{role: string, module: string}>                $restrictions
     * @param list<array{role: string, module: string, itemId: int}>   $items
     */
    public static function fromRows(array $restrictions, array $items): self
    {
        $allowed = [];
        foreach ($restrictions as $restriction) {
            $allowed[$restriction['role']][$restriction['module']] = [];
        }
        foreach ($items as $item) {
            // položka bez příznaku omezení se ignoruje - neomezenou roli nesmí omezit
            if (isset($allowed[$item['role']][$item['module']])) {
                $allowed[$item['role']][$item['module']][] = $item['itemId'];
            }
        }

        return new self($allowed);
    }

    /**
     * @return ?list<int> null = role nemá v modulu omezení
     */
    public function getAllowedIds(string $role, string $module): ?array
    {
        return $this->allowed[$role][$module] ?? null;
    }
}
