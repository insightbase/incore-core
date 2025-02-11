<?php

namespace App\UI\Translate\Form;

use App\Component\Translator\Translator;
use App\Model\Entity\TranslateEntity;
use App\Model\Language;
use App\Model\TranslateLanguage;
use App\UI\Accessory\Form\Form;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Form\FormFactory $formFactory,
        private Translator $translator,
        private Language $languageModel,
        private TranslateLanguage $translateLanguageModel,
    ) {}

    /**
     * @param TranslateEntity $translate
     */
    public function createTranslate(ActiveRow $translate): Form
    {
        $form = $this->formFactory->create();

        $languageInput = $form->addContainer('languageInput');
        foreach ($this->languageModel->getToTranslate() as $language) {
            $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $language);
            $languageInput->addText($language->id, $language->name.' ( '.$language->locale.' )')
                ->setNullable()
                ->setDefaultValue(null === $translateLanguage ? '' : $translateLanguage->value)
            ;
        }

        $form->addSubmit('send', $this->translator->translate('submit_translate'));

        return $form;
    }
}
