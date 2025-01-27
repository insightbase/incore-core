<?php

namespace App\UI\Language\DataGrid;

use App\Component\Datagrid\DefaultIconEnum;
use App\Component\Datagrid\Entity\BooleanColumnEntity;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Translator\Translator;
use App\Model\Language;
use App\UI\Language\DataGrid\Exception\DefaultLanguageRequiredException;
use App\UI\Language\LanguageFacade;

readonly class DefaultDataGridEntityFactory
{
    public function __construct(
        private Translator $translator,
        private Language $languageModel,
        private LanguageFacade $languageFacade,
    ) {}

    public function create(): DataGridEntity
    {
        $dataGridEntity = new DataGridEntity();

        $dataGridEntity
            ->addColumn(new ColumnEntity('id', $this->translator->translate('id')))
            ->addColumn(
                (new ColumnEntity('name', $this->translator->translate('column_name')))
                    ->setEnableSearchGlobal()
            )
            ->addColumn(
                (new ColumnEntity('locale', $this->translator->translate('column_locale')))
                    ->setEnableSearchGlobal()
            )
            ->addColumn(
                (new BooleanColumnEntity('is_default', $this->translator->translate('column_is_default')))
                    ->setOnClickCallback(function (int $id): void {
                        $language = $this->languageModel->get($id);
                        if ($language->is_default) {
                            throw new DefaultLanguageRequiredException($this->translator->translate('flash_default_language_required'));
                        }
                        $this->languageFacade->changeDefault($language);
                    })
            )
            ->addColumn(new ColumnEntity('url', $this->translator->translate('column_url')))
            ->addColumn(
                (new BooleanColumnEntity('active', $this->translator->translate('column_is_active')))
                    ->setOnClickCallback(function (int $id): void {
                        $language = $this->languageModel->get($id);
                        $this->languageFacade->changeActive($language);
                    })
            )
        ;

        $dataGridEntity->addMenu(
            (new MenuEntity($this->translator->translate('menu_edit'), 'edit'))
                ->setIcon(DefaultIconEnum::Edit->value)
        );
        $dataGridEntity->addMenu(
            (new MenuEntity($this->translator->translate('menu_delete'), 'delete'))
                ->setIcon(DefaultIconEnum::Delete->value)
        );

        return $dataGridEntity;
    }
}
