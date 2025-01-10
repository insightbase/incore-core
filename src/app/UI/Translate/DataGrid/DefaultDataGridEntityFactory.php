<?php

namespace App\UI\Translate\DataGrid;

use App\Component\Datagrid\DefaultIconEnum;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Translator\Translator;
use App\Model\Language;
use App\Model\TranslateLanguage;
use Nette\Database\Table\ActiveRow;

readonly class DefaultDataGridEntityFactory
{
    public function __construct(
        private Translator $translator,
        private Language $languageModel,
        private TranslateLanguage $translateLanguageModel,
    )
    {
    }

    public function create(): DataGridEntity
    {
        $entity = new DataGridEntity();

        $entity
            ->addColumn((new ColumnEntity('key', $this->translator->translate('column_key')))
                ->setEnableSearchGlobal()
            )
        ;
        foreach($this->languageModel->getToTranslate() as $language){
            $entity->addColumn((new ColumnEntity(':translate_language.value', $language->name . ' ( ' . $language->locale . ' )'))
                ->setEnableSearchGlobal()
                ->setGetRowCallback(function(ActiveRow $row) use ($language):string{
                    $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($row, $language);
                    if($translateLanguage === null){
                        return '';
                    }else{
                        return $translateLanguage->value;
                    }
                })
            );
        }

        $entity->addMenu((new MenuEntity($this->translator->translate('menu_translate'), 'translate'))
            ->setIcon(DefaultIconEnum::Edit->value)
        );

        return $entity;
    }
}