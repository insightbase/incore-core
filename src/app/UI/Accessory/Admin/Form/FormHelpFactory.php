<?php

namespace App\UI\Accessory\Admin\Form;

use App\Component\Translator\Translator;
use App\Model\Entity\FormHelpEntity;

readonly class FormHelpFactory
{
    public function __construct(
        private FormFactory $formFactory,
        private Translator  $translator,
    )
    {
    }

    public function createEdit():Form
    {
        $form = $this->formFactory->create();
        $form->showHelp = false;

        $form->addText('label_help', $this->translator->translate('input_label'))
            ->setRequired(false)
            ->setNullable()
            ->setHtmlAttribute($form::LANG_CHANGE_ATTRIBUTE)
        ;

        $form->addHidden('input_html_id');
        $form->addSubmit('send', $this->translator->translate('input_update'));

        return $form;
    }
}