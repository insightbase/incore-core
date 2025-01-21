<?php

namespace App\UI\Language\Form;

use App\Component\Translator\Translator;
use App\UI\Accessory\Form\Form;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Form\FormFactory $formFactory,
        private Translator                         $translator,
    )
    {
    }

    public function createNew():Form
    {
        $form = $this->createBase();
        $form->addSubmit('send', $this->translator->translate('submit_create'));
        return $form;
    }

    private function createBase():Form
    {
        $form = $this->formFactory->create();

        $form->addText('name', $this->translator->translate('input_name'))
            ->setRequired();
        $form->addText('locale', $this->translator->translate('input_locale'))
            ->setRequired();
        $form->addText('url', $this->translator->translate('input_url'))
            ->addRule($form::MaxLength, $this->translator->translate('input_url_max_length_%length%', 10), 10);
        $form->addDropzone('flag', $this->translator->translate('input_flag'))
            ->setNullable();
        return $form;
    }

    public function createEdit(ActiveRow $language):Form
    {
        $form = $this->createBase();
        $form->addSubmit('send', $this->translator->translate('submit_edit'));

        $form->setDefaults($language->toArray());

        return $form;
    }
}