<?php

namespace App\UI\Admin\StaticPage\Form;

use App\Component\Translator\Translator;
use App\Model\Admin\Language;
use App\Model\Admin\StaticPageLanguage;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Admin\StaticPage\StaticPageFacade;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Admin\Form\FormFactory $formFactory,
        private Translator $translator,
        private Language $languageModel,
        private StaticPageFacade $staticPageFacade,
        private StaticPageLanguage $staticPageLanguageModel,
    )
    {
    }

    public function create(?ActiveRow $staticPage = null):Form
    {
        $form = $this->formFactory->create();

        $form->addGroup($this->translator->translate('group_staticPageGeneral'));
        $form->addCheckbox('active', $this->translator->translate('input_staticPageActive'));

        if($staticPage !== null){
            $defaults = $staticPage->toArray();
        }else{
            $form->addText('system_name', $this->translator->translate('input_staticPageSystemName'))
                ->setRequired();
            $defaults = [];
        }

        foreach($this->languageModel->getToTranslate() as $language) {
            $form->addGroupLanguage($language);
            $container = $form->addContainer('language_' . $language->id);
            $container->setMappedType(LanguageData::class);
            $container->setToggle($this->translator->translate('group_staticPage_general'));

            if($staticPage !== null && $language->is_default) {
                $defaults['language_' . $language->id] = $staticPage->toArray();
                $defaults['language_' . $language->id]['seo_language_' . $language->id] = $staticPage->toArray();
            }

            $name = $container->addText('name', $this->translator->translate('input_staticPageName'))
                ->setNullable();
            $container->addSlug('slug', $staticPage === null ? $name : null, $this->translator->translate('input_staticPageSlug'))
                ->setNullable();
            $container->addEditorJs('content', $this->translator->translate('input_staticPageContent'));

            $seo = $container->addContainer('seo_language_' . $language->id);
            $seo->setMappedType(LanguageSeoData::class);
            $seo->setToggle($this->translator->translate('group_staticPage_seo'), true);
            $seo->addCopy('title', $name, $this->translator->translate('input_staticPageTitle'))
                ->setNullable();
            $seo->addTextArea('description', $this->translator->translate('input_staticPageDescription'))
                ->setNullable();
            $seo->addText('keywords', $this->translator->translate('input_staticPageKeywords'))
                ->setNullable();
        }

        $form->addGroup();
        if($staticPage === null){
            $form->addSubmit('send', $this->translator->translate('input_staticPageCreate'));
        }else{
            $form->addSubmit('send', $this->translator->translate('input_staticPageUpdate'));
        }

        if($staticPage === null) {
            $form->setDefaults(['active' => true]);
            $form->onSuccess[] = function (Form $form, NewData $data): void {
                $this->staticPageFacade->create($data);
            };
        }else{
            foreach($this->languageModel->getToTranslateNotDefault() as $language1){
                $staticPageLanguage = $this->staticPageLanguageModel->getByStaticPageAndLanguage($staticPage, $language1);
                if($staticPageLanguage !== null){
                    $defaults['language_' . $language1->id] = $staticPageLanguage->toArray();
                    $defaults['language_' . $language1->id]['seo_language_' . $language1->id] = $staticPageLanguage->toArray();
                }
            }
            $form->setDefaults($defaults);
            $form->onSuccess[] = function (Form $form, EditData $data) use ($staticPage): void {
                $this->staticPageFacade->update($staticPage, $data);
            };
        }

        return $form;
    }
}