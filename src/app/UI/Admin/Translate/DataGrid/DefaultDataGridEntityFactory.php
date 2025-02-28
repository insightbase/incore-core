<?php

namespace App\UI\Admin\Translate\DataGrid;

use App\Component\Datagrid\DefaultIconEnum;
use App\Component\Datagrid\Dto\ReturnInlineEditCallback;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Translator\Translator;
use App\Model\Admin\Language;
use App\Model\Admin\TranslateLanguage;
use Nette\Database\Table\ActiveRow;

readonly class DefaultDataGridEntityFactory
{
    public function __construct(
        private Translator $translator,
        private Language $languageModel,
        private TranslateLanguage $translateLanguageModel,
    ) {}

    public function create(): DataGridEntity
    {
        $entity = new DataGridEntity();

        $entity
            ->addColumn(
                (new ColumnEntity('key', $this->translator->translate('column_key')))
                    ->setEnableSearchGlobal()
            )
        ;
        foreach ($this->languageModel->getToTranslate() as $language) {
            $entity->addColumn(
                (new ColumnEntity(':translate_language.value', $language->name.' ( '.$language->locale.' )'))
                    ->setEnableSearchGlobal()
                    ->setGetColumnCallback(function (ActiveRow $row) use ($language): string {
                        $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($row, $language);
                        if (null === $translateLanguage) {
                            return '';
                        }

                        return $translateLanguage->value;
                    })
                    ->setGetInlineEditIdCallback(function (ActiveRow $row) use ($language): string {
                        return $row['id'].'-'.$language->id;
                    })
                    ->setInlineEditCallback(function (string $id, string $value): ReturnInlineEditCallback {
                        $id = explode('-', $id);
                        $translateLanguage = $this->translateLanguageModel->getByTranslateIdAndLanguageId((int) $id[0], (int) $id[1]);
                        if ($translateLanguage) {
                            if ('' === $value) {
                                $translateLanguage->delete();
                            } else {
                                $translateLanguage->update(['value' => $value]);
                            }
                        } else {
                            if ('' !== $value) {
                                $this->translateLanguageModel->insert([
                                    'translate_id' => $id[0],
                                    'language_id' => $id[1],
                                    'value' => $value,
                                ]);
                            }
                        }

                        return new ReturnInlineEditCallback(redraw: true);
                    })
            );
        }

        $entity->addMenu(
            (new MenuEntity($this->translator->translate('menu_translate'), 'translate'))
                ->setIcon(DefaultIconEnum::Edit->value)
        );

        return $entity;
    }
}
