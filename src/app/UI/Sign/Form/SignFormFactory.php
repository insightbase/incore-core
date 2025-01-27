<?php

namespace App\UI\Sign\Form;

use App\Component\Translator\Translator;
use App\UI\Accessory\Form\Form;
use App\UI\Accessory\Form\FormFactory;

readonly class SignFormFactory
{
    public function __construct(
        private FormFactory $formFactory,
        private Translator $translator,
    ) {}

    public function createResetPassword(): Form
    {
        $form = $this->formFactory->create();
        $password = $form->addPassword('password', $this->translator->translate('input_newPassword'))
            ->setRequired()
        ;
        $form->addPassword('password1', $this->translator->translate('input_confimNewPassword'))
            ->setRequired()
            ->addRule($form::Equal, $this->translator->translate('error_bothPasswordMusetBeSame'), $password)
        ;
        $form->addSubmit('send', $this->translator->translate('submit_set'));

        return $form;
    }

    public function createForgotPassword(): Form
    {
        $form = $this->formFactory->create();

        $form->addText('email', $this->translator->translate('input_email'))
            ->setRequired()
        ;
        $form->addSubmit('send', $this->translator->translate('submit_continue'));

        return $form;
    }

    public function create(): Form
    {
        $form = $this->formFactory->create();

        $form->addText('email', $this->translator->translate('input_email'))
            ->setRequired()
        ;
        $form->addPassword('password', $this->translator->translate('input_password'))
            ->setRequired()
        ;
        $form->addCheckbox('rememberMe', $this->translator->translate('input_rememberMe'));
        $form->addSubmit('send', $this->translator->translate('submit_logIn'));

        return $form;
    }
}
