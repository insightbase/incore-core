<?php

namespace App\UI\Admin\Email\DataGrid;

use App\Component\Datagrid\DefaultIconEnum;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\DeleteMenuEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Translator\Translator;

readonly class DataGridEntityFactory
{
    public function __construct(
        private Translator $translator,
    )
    {
    }

    public function create():DataGridEntity
    {
        $dataGridEntity = new DataGridEntity();

        $dataGridEntity
            ->addColumn(
                new ColumnEntity('name', $this->translator->translate('column_name'))
                    ->setEnableSearchGlobal()
            )
            ->addColumn(
                new ColumnEntity('subject', $this->translator->translate('column_subject'))
                    ->setEnableSearchGlobal()
            )
        ;

        $dataGridEntity
            ->addMenu(
                new MenuEntity($this->translator->translate('menu_edit'), 'edit')
                    ->setIcon(DefaultIconEnum::Edit->value)
            )
            ->addMenu(
                new DeleteMenuEntity($this->translator->translate('menu_delete'), 'delete')
            )
        ;

        return $dataGridEntity;
    }
}