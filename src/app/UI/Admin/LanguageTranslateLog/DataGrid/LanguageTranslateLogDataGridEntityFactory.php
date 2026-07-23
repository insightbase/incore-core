<?php

namespace App\UI\Admin\LanguageTranslateLog\DataGrid;

use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\DateTimeColumnEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Datagrid\Entity\UserColumnEntityFactory;
use App\Component\Translator\Translator;
use Nette\Database\Table\ActiveRow;

readonly class LanguageTranslateLogDataGridEntityFactory
{
    public function __construct(
        private Translator $translator,
        private UserColumnEntityFactory $userColumnEntityFactory,
    ) {}

    public function create(): DataGridEntity
    {
        $dataGridEntity = new DataGridEntity();

        $dataGridEntity
            ->addColumn(new DateTimeColumnEntity('datetime', $this->translator->translate('column_languageTranslateLog_datetime')))
            ->addColumn(
                (new ColumnEntity('language_id', $this->translator->translate('column_languageTranslateLog_language')))
                    ->disableSort()
                    ->setGetColumnCallback(function (ActiveRow $row): string {
                        $language = $row->ref('language', 'language_id');

                        return null === $language ? '' : (string) $language['name'];
                    })
            )
            ->addColumn($this->userColumnEntityFactory->create('user_id', $this->translator->translate('column_languageTranslateLog_user')))
            ->addColumn(new ColumnEntity('drop_core_id', $this->translator->translate('column_languageTranslateLog_dropCoreId')))
            ->addColumn(new DateTimeColumnEntity('finished', $this->translator->translate('column_languageTranslateLog_finished')))
            ->addColumn(
                (new ColumnEntity('request', $this->translator->translate('column_languageTranslateLog_request')))
                    ->disableSort()
                    ->setTruncate(60)
            )
        ;

        $dataGridEntity->addMenu(
            (new MenuEntity($this->translator->translate('menu_languageTranslateLog_detail'), 'detail'))
                ->setIcon('ki-filled ki-eye')
        );

        return $dataGridEntity;
    }
}
