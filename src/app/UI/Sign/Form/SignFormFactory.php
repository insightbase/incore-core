<?php

namespace App\UI\Sign\Form;

use App\Component\Translator\Translator;
use App\UI\Accessory\Form\Form;
use App\UI\Accessory\Form\FormFactory;

readonly class SignFormFactory
{
    public function __construct(
        private FormFactory $formFactory,
        private Translator  $translator,
    )
    {
    }

    public function createResetPassword():Form
    {
        $form = $this->formFactory->create();
        $password = $form->addPassword('password', $this->translator->translate('Nové heslo'))
            ->setRequired();
        $form->addPassword('password1', $this->translator->translate('Potvrzení nového hesla'))
            ->setRequired()
            ->addRule($form::Equal, $this->translator->translate('Obě hesla se musí shodovat'), $password)
        ;
        $form->addSubmit('send', $this->translator->translate('Nastavit'));
        return $form;
    }

    public function createForgotPassword():Form{
        $form = $this->formFactory->create();

        $form->addText('email', $this->translator->translate('Email'))
            ->setRequired();
        $form->addSubmit('send', $this->translator->translate('Pokračovat'));

        return $form;
    }

    public function create():Form
    {
        $form = $this->formFactory->create();

        $form->addText('email', $this->translator->translate('Email'))
            ->setRequired();
        $form->addPassword('password', $this->translator->translate('Heslo'))
            ->setRequired();
        $form->addCheckbox('rememberMe', $this->translator->translate('Zapamatovat si mě'));
        $form->addSubmit('send', $this->translator->translate('Přihlásit se'));

        return $form;
    }
}