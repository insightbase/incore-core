<?php

namespace App\UI\Setting\Form;

use App\Component\Translator\Translator;
use App\UI\Accessory\Form\Form;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Form\FormFactory $formFactory,
        private Translator $translator,
    ) {}

    public function createTestEmail(): Form
    {
        $form = $this->formFactory->create();

        $form->addEmail('email', $this->translator->translate('input_receiver'))
            ->setRequired()
        ;
        $form->addTextArea('message', $this->translator->translate('input_message'))
            ->setRequired()
        ;
        $form->addSubmit('send', $this->translator->translate('send_send'));

        return $form;
    }

    public function createEdit(?ActiveRow $setting): Form
    {
        $form = $this->formFactory->create();

        $form->addGroup($this->translator->translate('field_general'));
        $form->addDropzone('logo', $this->translator->translate('input_logo'))
            ->setNullable()
        ;
        $form->addDropzone('logo_small', $this->translator->translate('input_logo_small'))
            ->setNullable()
        ;
        $form->addDropzone('logo_dark', $this->translator->translate('input_logo_dark'))
            ->setNullable()
        ;
        $form->addDropzone('logo_dark_small', $this->translator->translate('input_logo_dark_small'))
            ->setNullable()
        ;
        $form->addDropzone('favicon', $this->translator->translate('input_favicon'))
            ->setNullable()
        ;
        $form->addDropzone('shareimage', $this->translator->translate('input_shareimage'))
            ->setNullable()
        ;

        $form->addGroup($this->translator->translate('field_email'));
        $form->addEmail('email', $this->translator->translate('input_email'))
            ->setRequired()
        ;
        $form->addEmail('email_sender', $this->translator->translate('input_email_sender'))
            ->setNullable()
        ;
        $form->addText('smtp_host', $this->translator->translate('input_smtp_host'))
            ->setNullable()
        ;
        $form->addText('smtp_username', $this->translator->translate('input_smtp_username'))
            ->setNullable()
        ;
        $form->addPassword('smtp_password', $this->translator->translate('input_smtp_password'))
            ->setNullable()
        ;

        $form->addGroup($this->translator->translate('field_recaptcha'));
        $form->addText('recaptcha_secret_key', $this->translator->translate('input_recaptcha_secret_key'))
            ->setNullable()
        ;
        $form->addText('recaptcha_site_key', $this->translator->translate('input_recaptcha_site_key'))
            ->setNullable()
        ;

        $form->addGroup();
        $form->addSubmit('send', $this->translator->translate('submit_update'));

        if (null !== $setting) {
            $form->setDefaults($setting->toArray());
        }

        return $form;
    }
}
