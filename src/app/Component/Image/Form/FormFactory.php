<?php

namespace App\Component\Image\Form;

use App\Component\Translator\Translator;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageLocationEnum;
use App\UI\Accessory\Admin\Form\Form;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Admin\Form\FormFactory $formFactory,
        private Translator                               $translator,
    )
    {
    }

    public function create():Form
    {
        $form = $this->formFactory->create();

        $form->sendByAjax();

        $form->addText('alt', $this->translator->translate('input_alt'))
            ->setNullable()
            ->setHtmlAttribute($form::LANG_CHANGE_ATTRIBUTE);
        $form->addText('name', $this->translator->translate('input_name'))
            ->setNullable()
            ->setHtmlAttribute($form::LANG_CHANGE_ATTRIBUTE);
        $form->addTextArea('description', $this->translator->translate('input_description'))
            ->setNullable()
            ->setHtmlAttribute($form::LANG_CHANGE_ATTRIBUTE);
        $form->addTextArea('author', $this->translator->translate('input_author'))
            ->setNullable()
            ->setHtmlAttribute($form::LANG_CHANGE_ATTRIBUTE);
        // vlastní soubor obrázku pro jazyk (prázdné = soubor výchozího jazyka), ve výchozím jazyce se nezobrazuje
        $form->addDropzoneImage(DropzoneImageLocationEnum::Content, 'file_id', $this->translator->translate('input_imageFile'))
            ->setNullable()
            ->setHtmlAttribute($form::LANG_CHANGE_ATTRIBUTE)
            ->setOption($form::HIDE_IN_DEFAULT_LANGUAGE_OPTION, true);
        $form->addHidden('image_id')
            ->setRequired()
            ->addRule($form::Integer)
            ->addFilter(fn($v) => (int) $v);
        $form->addSubmit('send', $this->translator->translate('send_update'));

        $form->applyMaxLengthFromEntity(\App\Model\DoctrineEntity\Image::class);

        return $form;
    }
}