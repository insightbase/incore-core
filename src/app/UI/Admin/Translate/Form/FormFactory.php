<?php

namespace App\UI\Admin\Translate\Form;

use App\Component\Translator\Translator;
use App\Model\Admin\Language;
use App\Model\Admin\TranslateLanguage;
use App\Model\Entity\TranslateEntity;
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
