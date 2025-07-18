<?php

namespace App\UI\Admin\Role\Form;

use App\Component\Translator\Translator;
use App\Model\Admin\ModulePrivilege;
use App\Model\Admin\Permission;
use App\Model\Entity\ModuleEntity;
use App\Model\Entity\RoleEntity;
use App\UI\Accessory\Admin\Form\Form;
use Nette\Database\Table\ActiveRow;

readonly class FormFactory
{
    public function __construct(
        private \App\UI\Accessory\Admin\Form\FormFactory $formFactory,
        private Translator                               $translator,
        private ModulePrivilege                          $modulePrivilege,
        private Permission                               $permissionModel,
    ) {}

    /**
     * @param RoleEntity   $role
     * @param ModuleEntity $module
     */
    public function createAuthorizationSet(ActiveRow $role, ActiveRow $module): Form
    {
        $form = $this->formFactory->create();

        $privileges = [];
        foreach ($this->modulePrivilege->getByModule($module) as $modulePrivilege) {
            $privileges[$modulePrivilege->privilege->id] = $modulePrivilege->privilege->name.' ( '.$modulePrivilege->privilege->system_name.' )';
        }
        $form->addCheckboxList('privileges', $this->translator->translate('input_privileges'), $privileges);

        $form->addSubmit('send', 'send_update');

        $defaultPrivilege = [];
        foreach ($this->permissionModel->getByRoleAndModule($role, $module) as $permission) {
            $defaultPrivilege[] = $permission->privilege->id;
        }
        $form->setDefaults(['privileges' => $defaultPrivilege]);

        return $form;
    }

    public function createEdit(ActiveRow $role): Form
    {
        $form = $this->createBase();
        $form->addSubmit('send', $this->translator->translate('send_update'));

        $form->setDefaults($role->toArray());

        return $form;
    }

    public function createNew(): Form
    {
        $form = $this->createBase();
        $form->addSubmit('send', $this->translator->translate('send_create'));

        return $form;
    }

    private function createBase(): Form
    {
        $form = $this->formFactory->create();

        $form->addText('name', $this->translator->translate('input_name'))
            ->setRequired()
        ;
        $form->addText('system_name', $this->translator->translate('input_system_name'))
            ->setRequired()
        ;

        return $form;
    }
}
