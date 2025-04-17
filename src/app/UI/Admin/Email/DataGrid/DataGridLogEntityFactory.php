<?php

namespace App\UI\Admin\Email\DataGrid;

use App\Component\Datagrid\DefaultIconEnum;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\DateTimeColumnEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Translator\Translator;

readonly class DataGridLogEntityFactory
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
                new DateTimeColumnEntity('created', $this->translator->translate('column_created'))
            )
            ->addColumn(
                new ColumnEntity('subject', $this->translator->translate('column_subject'))
                    ->setEnableSearchGlobal()
            )
            ->addColumn(
                new ColumnEntity('address', $this->translator->translate('column_address'))
                    ->setEnableSearchGlobal()
            )
            ->addColumn(
                new ColumnEntity('error', $this->translator->translate('column_error'))
                    ->setEnableSearchGlobal()
            )
        ;

        $dataGridEntity
            ->addMenu(
                new MenuEntity($this->translator->translate('menu_show'), 'show')
                    ->setIcon(DefaultIconEnum::Edit->value)
            )
        ;

        return $dataGridEntity;
    }
}