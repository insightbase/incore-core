<?php

namespace App\UI\Role\DataGrid;

use App\Component\Datagrid\DefaultIconEnum;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Translator\Translator;
use Nette\Database\Table\ActiveRow;

readonly class DefaultEntityFactory
{
    public function __construct(
        private Translator $translator,
    )
    {
    }

    public function create():DataGridEntity
    {
        $entity = new DataGridEntity();

        $entity->addColumn((new ColumnEntity('name', $this->translator->translate('column_name')))
            ->setEnableSearchGlobal()
        );

        $entity->addMenu((new MenuEntity($this->translator->translate('menu_edit'), 'edit'))
            ->setIcon(DefaultIconEnum::Edit->value)
            ->setShowCallback(function(ActiveRow $row):bool{
                return !$row['is_systemic'];
            })
        );
        $entity->addMenu((new MenuEntity($this->translator->translate('menu_authorization'), 'authorization'))
            ->setIcon('ki-filled ki-key')
            ->setShowCallback(function(ActiveRow $row):bool{
                return !$row['is_systemic'];
            })
        );

        return $entity;
    }
}