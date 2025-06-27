<?php

namespace App\UI\Admin\Log\DataGrid;

use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\DateTimeColumnEntity;
use App\Component\Datagrid\Entity\UserColumnEntityFactory;
use App\Component\Log\LogActionEnum;
use App\Component\Translator\Translator;
use Nette\Database\Table\ActiveRow;

readonly class DefaultDataGridEntityFactory
{
    public function __construct(
        private Translator              $translator,
        private UserColumnEntityFactory $userColumnEntityFactory,
    )
    {
    }

    public function create():DataGridEntity
    {
        $dataGridEntity = new DataGridEntity();
        $dataGridEntity->addColumn(new ColumnEntity('table', $this->translator->translate('column_module'), true));
        $dataGridEntity->addColumn(new DateTimeColumnEntity('created', $this->translator->translate('column_date')));
        $dataGridEntity->addColumn($this->userColumnEntityFactory->create('user_id', $this->translator->translate('column_user')));
        $dataGridEntity->addColumn(new ColumnEntity('action', $this->translator->translate('column_action'))
            ->setGetColumnCallback(function(ActiveRow $activeRow):string{
                $action = LogActionEnum::from($activeRow['action']);
                return $action->translate($this->translator);
            })
        );

        return $dataGridEntity;
    }
}