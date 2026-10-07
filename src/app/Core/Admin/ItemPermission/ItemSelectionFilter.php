<?php

namespace App\Core\Admin\ItemPermission;

use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

final class ItemSelectionFilter
{
    /**
     * @template T of ActiveRow
     * @param Selection<T> $selection
     * @param ?list<int>   $allowedIds null = bez omezení
     * @return Selection<T>
     */
    public static function apply(Selection $selection, ?array $allowedIds, string $column = 'id'): Selection
    {
        if ($allowedIds === null) {
            return $selection;
        }
        if ($allowedIds === []) {
            // omezená role bez položek nesmí vidět nic - nikdy nevracet nefiltrovanou selekci
            return $selection->where('1 = 0');
        }

        return $selection->where($column, $allowedIds);
    }
}
