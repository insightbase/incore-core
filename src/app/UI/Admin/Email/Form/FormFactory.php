<?php

namespace App\UI\Admin\Email\Form;

use App\Component\Translator\Translator;
use App\UI\Accessory\Admin\Form\Form;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Admin\Form\FormFactory $formFactory,
        private Translator                               $translator,
    )
    {
    }

    public function createEdit(ActiveRow $email):Form{
        $form = $this->createBase();
        $form->addSubmit('send', $this->translator->translate('Update'));
        $form->setDefaults($email->toArray());
        return $form;
    }

    public function createNew():Form{
        $form = $this->createBase();
        $form->addSubmit('send', $this->translator->translate('Create'));
        return $form;
    }

    private function createBase():Form
    {
        $form = $this->formFactory->create();

        $form->addText('name', $this->translator->translate('input_name'))
            ->setRequired()
        ;
        $form->addText('system_name', $this->translator->translate('input_systemName'))
            ->setRequired()
        ;
        $form->addText('subject', $this->translator->translate('input_subject'))
            ->setRequired()
        ;
        $form->addText('template', $this->translator->translate('input_template'))
            ->setNullable()
        ;
        $form->addTextArea('text', $this->translator->translate('input_text'))
            ->setNullable()
        ;

        $form->addText('modifier', $this->translator->translate('input_modifier'))
            ->setNullable()
        ;

        return $form;
    }
}