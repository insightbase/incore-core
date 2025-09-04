<?php

namespace App\UI\Admin\Module\DataGrid;

use App\Component\Datagrid\DefaultIconEnum;
use App\Component\Datagrid\Entity\BooleanColumnEntity;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Datagrid\SortDirEnum;
use App\Component\Translator\Translator;
use App\Model\Admin\Module;

readonly class DefaultDataGridEntityFactory
{
    public function __construct(
        private Translator $translator,
        private Module $moduleModel,
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
            ->addColumn((new BooleanColumnEntity('active', $this->translator->translate('column_active')))
                ->setOnClickCallback(function (int $id): void {
                    $module = $this->moduleModel->get($id);
                    $module->update(['active' => !$module->active]);
                })
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
