<?php

namespace App\Component\Image\Form;

use App\Component\Translator\Translator;
use App\UI\Accessory\Form\Form;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Form\FormFactory $formFactory,
        private Translator                         $translator,
    )
    {
    }

    public function create():Form
    {
        $form = $this->formFactory->create();

        $form->sendByAjax();

        $form->addText('alt', $this->translator->translate('input_alt'))
            ->setNullable();
        $form->addText('name', $this->translator->translate('input_name'))
            ->setNullable();
        $form->addTextArea('description', $this->translator->translate('input_description'))
            ->setNullable();
        $form->addTextArea('author', $this->translator->translate('input_author'))
            ->setNullable();
        $form->addHidden('image_id')->addRule($form::Integer);
        $form->addSubmit('send', $this->translator->translate('send_update'));
        return $form;
    }
}