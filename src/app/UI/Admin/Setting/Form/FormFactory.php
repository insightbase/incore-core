<?php

namespace App\UI\Admin\Setting\Form;

use App\Component\Translator\Translator;
use App\Model\Entity\SettingEntity;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageLocationEnum;
use App\UI\Accessory\Admin\Form\Form;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Admin\Form\FormFactory $formFactory,
        private Translator                               $translator,
    ) {}

    /**
     * @param ?SettingEntity $setting
     * @return Form
     */
    public function createAnalytics(?ActiveRow $setting):Form
    {
        $form = $this->formFactory->create();

        $form->addText('ga_service_id', $this->translator->translate('input_settingGoogleAnalyticsServiceId'))
            ->setDefaultValue($setting?->ga_service_id)
            ->setNullable();
        $form->addSubmit('send', $this->translator->translate('send_send'));

        return $form;
    }

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
        $form->addDropzoneImage(DropzoneImageLocationEnum::SettingLogo, 'logo_id', $this->translator->translate('input_logo'))
            ->setNullable()
        ;
        $form->addDropzoneImage(DropzoneImageLocationEnum::SettingLogo, 'logo_small_id', $this->translator->translate('input_logo_small'))
            ->setNullable()
        ;
        $form->addDropzoneImage(DropzoneImageLocationEnum::SettingLogo, 'logo_dark_id', $this->translator->translate('input_logo_dark'))
            ->setNullable()
        ;
        $form->addDropzoneImage(DropzoneImageLocationEnum::SettingLogo, 'logo_dark_small_id', $this->translator->translate('input_logo_dark_small'))
            ->setNullable()
        ;
        $form->addDropzoneImage(DropzoneImageLocationEnum::SettingShareImage, 'shareimage_id', $this->translator->translate('input_shareimage'))
            ->setNullable()
        ;
        $form->addDropzoneImage(DropzoneImageLocationEnum::SettingPlaceholder, 'placeholder_id', $this->translator->translate('input_placeholder'))
            ->setNullable()
        ;
        $form->addCheckbox('translate_expand_keys', $this->translator->translate('input_translate_expand_keys'));
        $form->addText('title', $this->translator->translate('input_title'))
            ->setRequired()
        ;
        $form->addText('title_subpage', $this->translator->translate('input_titleSubpage'))
            ->setNullable()
        ;

        $form->addGroup($this->translator->translate('field_email'));
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

        $form->addGroup($this->translator->translate('field_images'));
        $form->addInteger('max_image_resolution', $this->translator->translate('input_maxImageResolution'))
            ->setNullable();

        $form->addGroup($this->translator->translate('field_googleAnalytics'));
        $form->addDropzoneFile('google_service_account_id', $this->translator->translate('input_settingGoogleServiceAccount'))
            ->setNullable();

        $form->addGroup($this->translator->translate('field_basicAuth'));
        $form->addText('basic_auth_user', $this->translator->translate('input_basicAuthUser'))
            ->setNullable();
        $form->addText('basic_auth_password', $this->translator->translate('input_basicAuthPassword'))
            ->setNullable();

        $form->addGroup();
        $form->addSubmit('send', $this->translator->translate('submit_update'));

        if (null !== $setting) {
            $form->setDefaults($setting->toArray());
        }

        return $form;
    }
}
