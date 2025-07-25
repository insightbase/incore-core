<?php

namespace App\UI\Admin\Module\DataGrid;

use App\Component\Datagrid\DefaultIconEnum;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Datagrid\SortDirEnum;
use App\Component\Translator\Translator;

readonly class DefaultDataGridEntityFactory
{
    public function __construct(
        private Translator $translator,
    ) {}

    public function create(): DataGridEntity
    {
        $entity = new DataGridEntity();
        $entity->setOrdering();
        $entity->setDefaultOrder('position');
        $entity->setDefaultOrderDir(SortDirEnum::ASC);
        $entity->setRedrawSnippetAfterOrdering('layoutMenu');

        $entity
            ->addColumn(
                (new ColumnEntity('name', $this->translator->translate('column_name')))
                    ->setEnableSearchGlobal()
            )
            ->addColumn(
                (new ColumnEntity('presenter', $this->translator->translate('column_presenter')))
                    ->setEnableSearchGlobal()
            )
        ;

        $entity
            ->addMenu(
                (new MenuEntity($this->translator->translate('menu_update'), 'edit'))
                    ->setIcon(DefaultIconEnum::Edit->value)
            )
        ;

        return $entity;
    }
}
