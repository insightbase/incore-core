<?php

namespace App\UI\Admin\Favicon\DataGrid;

use App\Component\Datagrid\DefaultIconEnum;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\DeleteMenuEntity;
use App\Component\Datagrid\Entity\ImageColumnEntity;
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

        $dataGridEntity->addColumn((new ColumnEntity('rel', $this->translator->translate('column_rel'))));
        $dataGridEntity->addColumn((new ColumnEntity('type', $this->translator->translate('column_type'))));
        $dataGridEntity->addColumn((new ColumnEntity('sizes', $this->translator->translate('column_sizes'))));
        $dataGridEntity->addColumn((new ColumnEntity('name', $this->translator->translate('column_name'))));
        $dataGridEntity->addColumn((new ColumnEntity('content', $this->translator->translate('column_content'))));
        $dataGridEntity->addColumn((new ImageColumnEntity('image_id', $this->translator->translate('column_image'))));

        $dataGridEntity->addMenu((new MenuEntity($this->translator->translate('menu_edit'), 'edit'))
            ->setIcon(DefaultIconEnum::Edit->value)
        );
        $dataGridEntity->addMenu((new DeleteMenuEntity($this->translator->translate('menu_delete'), 'delete'))
        );

        return $dataGridEntity;
    }
}