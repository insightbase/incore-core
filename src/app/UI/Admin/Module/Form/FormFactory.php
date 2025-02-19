<?php

namespace App\UI\Admin\Module\Form;

use App\Component\Translator\Translator;
use App\Model\Admin\Module;
use App\UI\Accessory\Admin\Form\Form;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Admin\Form\FormFactory $formFactory,
        private Translator                               $translator,
        private Module                                   $moduleModel,
    ) {}

    public function createEdit(ActiveRow $module): Form
    {
        $form = $this->formFactory->create();

        $modules = $this->moduleModel->getNotParent()->fetchPairs('id', 'name');

        $form->addText('name', $this->translator->translate('input_name'))
            ->setRequired()
        ;
        $form->addText('system_name', $this->translator->translate('input_systemName'))
            ->setRequired()
        ;
        $form->addText('presenter', $this->translator->translate('input_presenter'))
            ->setNullable()
        ;
        $form->addText('icon', $this->translator->translate('input_icon'))
            ->setNullable()
        ;
        $form->addSelect('parent_id', $this->translator->translate('input_parent'), $modules)
            ->setPrompt('prompt_selectModule')
        ;

        $form->addSubmit('send', $this->translator->translate('input_update'));

        $form->setDefaults($module->toArray());

        return $form;
    }
}
