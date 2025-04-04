<?php

namespace App\UI\Admin\Translate\DataGrid;

use App\Component\Datagrid\Column\Column;
use App\Component\EditorJs\EditorJsFacade;
use App\Component\Translator\Translator;
use App\Model\Admin\Translate;
use App\Model\Admin\TranslateLanguage;
use App\Model\Enum\TranslateTypeEnum;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\Form\FormFactory;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Table\ActiveRow;

readonly class InlineEdit implements \App\Component\Datagrid\InlineEdit
{
    public function __construct(
        private ActiveRow         $language,
        private Translate         $translateModel,
        private TranslateLanguage $translateLanguageModel,
        private Storage $storage,
        private FormFactory $formFactory,
        private Translator $translator,
    )
    {
    }

    public function isEnabled(ActiveRow $row): bool
    {
        return true;
    }

    public function getDefaults(int $id): array
    {
        $translate = $this->translateModel->get($id);
        $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $this->language);
        $type = TranslateTypeEnum::from($translate->type);
        if($translateLanguage !== null){
            return [
                'value_text' => $type === TranslateTypeEnum::Text ? $translateLanguage->value : null,
                'value_html' => $type === TranslateTypeEnum::Html ? $translateLanguage->value : null,
                'type' => $type->value,
            ];
        }else{
            return [
                'value_text' => null,
                'value_html' => null,
                'type' => $type->value,
            ];
        }
    }

    public function getOnSuccessCallback(): callable
    {
        return function(array $values):void{
            $translate = $this->translateModel->get($values['id']);
            $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $this->language);
            $type = TranslateTypeEnum::from($values['type']);
            $value = match($type){
                TranslateTypeEnum::Text => $values['value_text'],
                TranslateTypeEnum::Html => $values['value_html'],
            };

            if ($translateLanguage !== null) {
                if ($value === null) {
                    $translateLanguage->delete();
                } else {
                    $translateLanguage->update([
                        'value' => $value,
                    ]);
                }
            } else {
                if ($value !== null) {
                    $this->translateLanguageModel->insert([
                        'translate_id' => $translate->id,
                        'language_id' => $this->language->id,
                        'value' => $value,
                    ]);
                }
            }
            $cache = new Cache($this->storage, Translator::CACHE_NAMESPACE);
            $cache->remove($this->language->id);
        };
    }

    public function getForm(): ?Form
    {
        $form = $this->formFactory->create();

        $type = $form->addRadioList('type', $this->translator->translate('input_type'), [
            TranslateTypeEnum::Text->value => $this->translator->translate('type_text'),
            TranslateTypeEnum::Html->value => $this->translator->translate('type_html'),
        ])->setOption('hidden', true);
        $type->addCondition($form::Equal, TranslateTypeEnum::Text->value)->toggle('valueText');
        $type->addCondition($form::Equal, TranslateTypeEnum::Html->value)->toggle('valueHtml');
        $form->addTextArea('value_text', $this->translator->translate('input_valueText'))
            ->setNullable()
            ->setOption('id', 'valueText')
        ;
        $form->addEditorJs('value_html', $this->translator->translate('input_valueHtml'))
            ->setNullable()
            ->setOption('id', 'valueHtml')
        ;

        return $form;
    }

    public function getHeader(int $id): string
    {
        $translate = $this->translateModel->get($id);
        return $translate->key;
    }
}