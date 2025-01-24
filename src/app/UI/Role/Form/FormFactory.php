<?php

namespace App\UI\Role\Form;

use App\Component\Translator\Translator;
use App\UI\Accessory\Form\Form;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Form\FormFactory $formFactory,
        private Translator                           $translator,
    )
    {
    }

    private function createBase():Form{
        $form = $this->formFactory->create();

        $form->addText('name', $this->translator->translate('input_name'))
            ->setRequired();
        $form->addText('system_name', $this->translator->translate('input_system_name'))
            ->setRequired();
        return $form;
    }

    public function createEdit(ActiveRow $role):Form
    {
        $form = $this->createBase();
        $form->addSubmit('send', $this->translator->translate('send_update'));

        $form->setDefaults($role->toArray());

        return $form;
    }

    public function createNew():Form
    {
        $form = $this->createBase();
        $form->addSubmit('send', $this->translator->translate('send_create'));

        return $form;
    }
}