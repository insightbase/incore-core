<?php

namespace App\UI\Admin\Role\DataGrid;

use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\HasManyColumnEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Translator\Translator;
use App\Model\Entity\RoleEntity;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class ModuleEntityFactory
{
    public function __construct(
        private Translator $translator,
    ) {}

    /**
     * @param RoleEntity $role
     */
    public function create(ActiveRow $role): DataGridEntity
    {
        $entity = new DataGridEntity();

        $entity->addColumn(
            (new ColumnEntity('name', $this->translator->translate('column_name')))
                ->setEnableSearchGlobal()
        );
        $entity->addColumn(
            (new HasManyColumnEntity('name', $this->translator->translate('column_privileges')))
                ->setRelation('permission')
                ->setRef(['privilege'])
                ->setGetRelationCallback(function (ActiveRow $activeRow) use ($role): Selection {
                    return $activeRow->related('permission')->where('role_id', $role->id)->where('active', true);
                })
        );

        $entity->addMenu(
            (new MenuEntity($this->translator->translate('Nastavit'), 'set'))
                ->setIcon('ki-filled ki-key')
                ->addParam('roleId', (string) $role->id)
        );

        return $entity;
    }
}
