<?php

namespace App\UI\Admin\Language\Form;

use App\Component\Translator\Translator;
use App\Model\Admin\LanguageSetting;
use App\Model\Entity\LanguageEntity;
use App\Model\Entity\LanguageSettingEntity;
use App\Model\Enum\LanguageSettingTypeEnum;
use App\UI\Accessory\Admin\Form\Form;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Admin\Form\FormFactory $formFactory,
        private Translator                               $translator,
        private LanguageSetting $languageSettingModel,
    ) {}

    /**
     * @param LanguageSettingEntity $languageSetting
     * @return Form
     */
    public function createSetting(ActiveRow $languageSetting):Form
    {
        $form = $this->formFactory->create();

        $form->addRadioList('type', $this->translator->translate('input_type'), [
            LanguageSettingTypeEnum::Url->value => $this->translator->translate('input_radio_languageByUrl'),
            LanguageSettingTypeEnum::Host->value => $this->translator->translate('input_radio_languageByHost'),
        ]);
        $form->addSubmit('send', $this->translator->translate('input_update'));

        $form->setDefaults($languageSetting->toArray());

        return $form;
    }

    public function createNew(): Form
    {
        $form = $this->createBase();
        $form->addSubmit('send', $this->translator->translate('submit_create'));

        return $form;
    }

    /**
     * @param LanguageEntity $language
     * @return Form
     */
    public function createEdit(ActiveRow $language): Form
    {
        $form = $this->createBase();
        $form->addSubmit('send', $this->translator->translate('submit_edit'));

        $form->setDefaults($language->toArray());

        return $form;
    }

    private function createBase(): Form
    {
        $form = $this->formFactory->create();

        $form->addText('name', $this->translator->translate('input_name'))
            ->setRequired()
        ;
        $form->addText('locale', $this->translator->translate('input_locale'))
            ->setRequired()
        ;
        $form->addText('url', $this->translator->translate('input_url'))
            ->addRule($form::MaxLength, $this->translator->translate('input_url_max_length_%length%', 10), 10)
        ;
        $form->addDropzone('flag_id', $this->translator->translate('input_flag'))
            ->setNullable()
        ;
        if($this->languageSettingModel->getSetting()->type === LanguageSettingTypeEnum::Host->value){
            $form->addText('host', $this->translator->translate('input_host'));
        }

        return $form;
    }
}
