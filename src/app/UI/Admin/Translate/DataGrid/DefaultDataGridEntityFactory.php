<?php

namespace App\UI\Admin\Translate\DataGrid;

use App\Component\Datagrid\DefaultIconEnum;
use App\Component\Datagrid\Dto\ReturnInlineEditCallback;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\FilterEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Datagrid\Enum\FilterTypeEnum;
use App\Component\EditorJs\EditorJsFacade;
use App\Component\Translator\Translator;
use App\Model\Admin\Language;
use App\Model\Admin\Translate;
use App\Model\Admin\TranslateLanguage;
use App\Model\Enum\TranslateTypeEnum;
use Nette\Application\Application;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class DefaultDataGridEntityFactory
{
    public function __construct(
        private Translator        $translator,
        private Language          $languageModel,
        private TranslateLanguage $translateLanguageModel,
        private InlineEditFactory        $inlineEditFactory,
        private EditorJsFacade $editorJsFacade,
        private Application $application,
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

                        $type = TranslateTypeEnum::from($row->type);
                        return match($type){
                            TranslateTypeEnum::Text => $translateLanguage->value,
                            TranslateTypeEnum::Html => $this->editorJsFacade->renderJson($translateLanguage->value),
                        };
                    })
                    ->setInlineEdit($this->inlineEditFactory->create($language))
                    ->setNoEscape()
//                    ->setGetInlineEditIdCallback(function (ActiveRow $row) use ($language): string {
//                        return $row['id'].'-'.$language->id;
//                    })
//                    ->setInlineEditCallback(function (string $id, string $value) use ($cache): ReturnInlineEditCallback {
//                        $id = explode('-', $id);
//                        $translateLanguage = $this->translateLanguageModel->getByTranslateIdAndLanguageId((int) $id[0], (int) $id[1]);
//                        if ($translateLanguage) {
//                            if ('' === $value) {
//                                $translateLanguage->delete();
//                            } else {
//                                $translateLanguage->update(['value' => $value]);
//                            }
//                        } else {
//                            if ('' !== $value) {
//                                $this->translateLanguageModel->insert([
//                                    'translate_id' => $id[0],
//                                    'language_id' => $id[1],
//                                    'value' => $value,
//                                ]);
//                            }
//                        }
//                        $cache->remove((int) $id[1]);
//
//                        return new ReturnInlineEditCallback(redraw: true);
//                    })
            );
        }
        if($this->application->getPresenter()->getName() !== 'Admin:Performance') {
            $entity->addMenu(
                (new MenuEntity($this->translator->translate('menu_translate'), 'translate'))
                    ->setIcon(DefaultIconEnum::Edit->value)
            );

            $entity->addFilter(
                (new FilterEntity($this->translator->translate('filter_onlyNotTranslated'), FilterTypeEnum::Checkbox))
                    ->setOnChangeCallback(function (Selection $model, string $value): void {
                        if ($value !== '' && $value !== 'false') {
                            $model->where('translate.id NOT IN ?', $this->translateLanguageModel->getTable()->select('translate.id'));
                        }
                    })
            );
        }

        return $entity;
    }
}
