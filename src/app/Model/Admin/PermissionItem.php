<?php

namespace App\Model\Admin;

use App\Model\Entity\PermissionItemEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection;

readonly class PermissionItem implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<PermissionItemEntity>
     */
    public function getTable(): Selection
    {
        /** @var Selection<PermissionItemEntity> $selection */
        $selection = $this->explorer->table('permission_item');

        return $selection;
    }

    /**
     * @return list<array{role: string, module: string, itemId: int}>
     */
    public function getAllForAcl(): array
    {
        $rows = [];
        foreach ($this->getTable()->select('role.system_name AS role_name, module.system_name AS module_name, item_id') as $row) {
            $rows[] = ['role' => (string) $row['role_name'], 'module' => (string) $row['module_name'], 'itemId' => (int) $row['item_id']];
        }

        return $rows;
    }

    /**
     * @return list<int>
     */
    public function getItemIds(int $roleId, int $moduleId): array
    {
        $ids = $this->getTable()->where('role_id', $roleId)->where('module_id', $moduleId)->fetchPairs(null, 'item_id');

        return array_values(array_map('intval', $ids));
    }

    public function insertIfMissing(int $roleId, int $moduleId, int $itemId): void
    {
        $exists = $this->getTable()
            ->where('role_id', $roleId)
            ->where('module_id', $moduleId)
            ->where('item_id', $itemId)
            ->count('*') > 0;
        if (!$exists) {
            $this->getTable()->insert(['role_id' => $roleId, 'module_id' => $moduleId, 'item_id' => $itemId]);
        }
    }

    /**
     * @param ?list<int> $itemIds null = všechny položky role v modulu
     */
    public function deleteByRoleAndModule(int $roleId, int $moduleId, ?array $itemIds = null): void
    {
        $selection = $this->getTable()->where('role_id', $roleId)->where('module_id', $moduleId);
        if ($itemIds !== null) {
            $selection->where('item_id', $itemIds);
        }
        $selection->delete();
    }

    public function deleteByModuleAndItem(int $moduleId, int $itemId): void
    {
        $this->getTable()->where('module_id', $moduleId)->where('item_id', $itemId)->delete();
    }
}
