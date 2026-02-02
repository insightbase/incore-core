<?php

namespace App\UI\Admin\Language\Form;

use App\Component\Translator\Translator;
use App\Model\Admin\Language;
use App\Model\Admin\LanguageLocale;
use App\Model\Admin\LanguageSetting;
use App\Model\Entity\LanguageEntity;
use App\Model\Entity\LanguageSettingEntity;
use App\Model\Enum\LanguageSettingTypeEnum;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageLocationEnum;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Admin\Action\Form\NewActionLanguageData;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Admin\Form\FormFactory $formFactory,
        private Translator                               $translator,
        private LanguageSetting $languageSettingModel,
        private Language $languageModel,
        private LanguageLocale $languageLocaleModel,
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

        $defaults = $language->toArray();
        foreach($this->languageModel->getToTranslateNotDefault() as $language1) {
            $languageLocale = $this->languageLocaleModel->getByLanguageAndLocale($language, $language1);
            if($languageLocale !== null) {
                $defaults['language_' . $language1->id] = $languageLocale->toArray();
            }
        }

        $form->setDefaults($defaults);

        return $form;
    }

    private function createBase(): Form
    {
        $form = $this->formFactory->create();

        $form->addGroup($this->translator->translate('group_languageGeneral'));
        $form->addText('name', $this->translator->translate('input_name'))
            ->setRequired()
        ;
        $form->addText('locale', $this->translator->translate('input_locale'))
            ->setRequired()
        ;
        $form->addText('url', $this->translator->translate('input_url'))
            ->addRule($form::MaxLength, $this->translator->translate('input_url_max_length_%length%', 10), 10)
        ;
        $form->addDropzoneImage(DropzoneImageLocationEnum::LanguageFlag, 'flag_id', $this->translator->translate('input_flag'))
            ->setNullable()
        ;
        if($this->languageSettingModel->getSetting()->type === LanguageSettingTypeEnum::Host->value){
            $form->addText('host', $this->translator->translate('input_host'));
        }

        foreach($this->languageModel->getToTranslateNotDefault() as $language) {
            $form->addGroupLanguage($language);
            $container = $form->addContainer('language_' . $language->id);
            $container->setMappedType(LanguageData::class);
            $container->setToggle($this->translator->translate('group_languageGeneral'));

            $container->addText('name', $this->translator->translate('input_name'))
                ->setNullable()
            ;
        }
        $form->addGroup();

        return $form;
    }
}
