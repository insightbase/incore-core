<?php

namespace App\UI\Admin\Email\Form;

use App\Component\Translator\Translator;
use App\Model\Admin\EmailLanguage;
use App\Model\Admin\Language;
use App\Model\Entity\EmailEntity;
use App\UI\Accessory\Admin\Form\Form;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Admin\Form\FormFactory $formFactory,
        private Translator                               $translator,
        private Language                                 $languageModel,
        private EmailLanguage                            $emailLanguageModel,
    )
    {
    }

    /**
     * @param EmailEntity $email
     */
    public function createEdit(ActiveRow $email):Form{
        $form = $this->createBase();
        $form->addSubmit('send', $this->translator->translate('Update'));
        $form->setDefaults($email->toArray());

        foreach ($this->languageModel->getToTranslateNotDefault() as $language) {
            $emailLanguage = $this->emailLanguageModel->getByEmailIdAndLanguage($email->id, $language);
            $form->setTranslates($language, [
                'subject' => $emailLanguage?->subject,
                'text' => $emailLanguage?->text,
            ]);
        }

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
            ->setHtmlAttribute($form::LANG_CHANGE_ATTRIBUTE)
        ;
        $form->addText('template', $this->translator->translate('input_template'))
            ->setNullable()
        ;
        $form->addTextArea('text', $this->translator->translate('input_text'))
            ->setNullable()
            ->setHtmlAttribute($form::LANG_CHANGE_ATTRIBUTE)
        ;

        $form->addText('modifier', $this->translator->translate('input_modifier'))
            ->setNullable()
        ;

        $form->applyMaxLengthFromEntity(\App\Model\DoctrineEntity\Email::class);

        return $form;
    }
}
