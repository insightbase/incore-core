<?php

namespace App\UI\Language\DataGrid;

use App\Component\Datagrid\DefaultIconEnum;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Translator\Translator;

readonly class DefaultDataGridEntityFactory
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
            ->addColumn((new ColumnEntity('id', $this->translator->translate('id'))))
            ->addColumn((new ColumnEntity('name', $this->translator->translate('column_name')))
                ->setEnableSearchGlobal()
            )
            ->addColumn((new ColumnEntity('locale', $this->translator->translate('column_locale')))
                ->setEnableSearchGlobal()
            )
        ;

        $dataGridEntity->addMenu((new MenuEntity($this->translator->translate('menu_edit'), 'edit'))
            ->setIcon(DefaultIconEnum::Edit->value)
        );
        $dataGridEntity->addMenu((new MenuEntity($this->translator->translate('menu_delete'), 'delete'))
            ->setIcon(DefaultIconEnum::Delete->value)
        );

        return $dataGridEntity;
    }
}