<?php

namespace App\UI\Admin\Favicon\Form;

use App\Component\Translator\Translator;
use App\Model\Entity\FaviconEntity;
use App\UI\Accessory\Admin\Form\Form;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Admin\Form\FormFactory $formFactory,
        private Translator                               $translator,
    )
    {
    }

    public function createImport():Form
    {
        $form = $this->formFactory->create();

        $form->addTextArea('html', $this->translator->translate('input_html'))
            ->setRequired();
        $dropzone = $form->addDropzone('files', $this->translator->translate('input_files'))
        ;
        $dropzone->multiple = true;
        $form->addSubmit('send', $this->translator->translate('input_import'));

        return $form;
    }

    /**
     * @param FaviconEntity $favicon
     * @return Form
     */
    public function createEdit(ActiveRow $favicon):Form
    {
        $form = $this->createBase();

        $form->addSubmit('send', 'input_update');

        $form->setDefaults($favicon->toArray());

        return $form;
    }

    public function createNew():Form
    {
        $form = $this->createBase();

        $form->addSubmit('send', 'input_create');

        return $form;
    }

    private function createBase():Form
    {
        $form = $this->formFactory->create();

        $form->addSelect('tag', $this->translator->translate('input_tag'), [
            'link' => 'link',
            'meta' => 'meta',
        ]);

        $form->addText('rel', $this->translator->translate('input_rel'))
            ->setNullable();

        $form->addText('type', $this->translator->translate('input_type'))
            ->setNullable();

        $form->addText('sizes', $this->translator->translate('input_sizes'))
            ->setNullable();

        $form->addText('href', $this->translator->translate('input_href'))
            ->setNullable();

        $form->addText('name', $this->translator->translate('input_name'))
            ->setNullable();

        $form->addText('content', $this->translator->translate('input_content'))
            ->setNullable();

        $form->addDropzone('image_id', $this->translator->translate('input_image'))
            ->setNullable();

        $form->addSelect('image_to_attribute', $this->translator->translate('input_imageToAttribute'), [
            'content' => 'content',
            'href' => 'href',
        ]);

        return $form;
    }
}