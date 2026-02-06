<?php

namespace App\UI\Admin\Translate\Form;

use App\Component\Translator\Translator;
use App\Model\Admin\Language;
use App\Model\Admin\TranslateLanguage;
use App\Model\Entity\TranslateEntity;
use App\Model\Enum\TranslateTypeEnum;
use App\UI\Accessory\Admin\Form\Form;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Admin\Form\FormFactory $formFactory,
        private Translator                               $translator,
        private Language                                 $languageModel,
        private TranslateLanguage                        $translateLanguageModel,
    ) {}

    public function createNew():Form
    {
        $form = $this->formFactory->create();

        $form->addText('key', $this->translator->translate('input_key'))
            ->setRequired();
        $form->addSelect('source', $this->translator->translate('input_source'), ['admin' => 'Admin', 'front' => 'Front']);

        $form->addSubmit('send', $this->translator->translate('input_create'));

        return $form;
    }

    /**
     * @param TranslateEntity $translate
     */
    public function createTranslate(ActiveRow $translate): Form
    {
        $form = $this->formFactory->create();

        foreach ($this->languageModel->getToTranslate() as $language) {
            $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $language);
            $key = TranslateTypeEnum::from($translate->type);
            match ($key){
                TranslateTypeEnum::Text => $form->addText($language->id, $language->name.' ( '.$language->locale.' )')
                    ->setNullable()
                    ->setDefaultValue(null === $translateLanguage ? '' : $translateLanguage->value),
                TranslateTypeEnum::Html => $form->addEditorJs($language->id, $language->name.' ( '.$language->locale.' )')
                    ->setNullable()
                    ->setDefaultValue(null === $translateLanguage ? '' : $translateLanguage->value),
            };
        }

        $form->addCheckbox('is_performance', $this->translator->translate('input_isPerformance'))
            ->setDefaultValue($translate->is_performance)
        ;

        $form->addSubmit('send', $this->translator->translate('submit_translate'));

        return $form;
    }
}
