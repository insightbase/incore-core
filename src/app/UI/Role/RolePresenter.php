<?php

namespace App\UI\Role;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Entity\RoleEntity;
use App\Model\Role;
use App\UI\Accessory\Form\Form;
use App\UI\Accessory\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Submenu\SubmenuFactory;
use App\UI\Role\DataGrid\DefaultEntityFactory;
use App\UI\Role\Exception\SystematicRoleException;
use App\UI\Role\Form\EditData;
use App\UI\Role\Form\FormFactory;
use App\UI\Role\Form\NewData;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;

class RolePresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    /**
     * @var ?RoleEntity
     */
    private ?ActiveRow $role;

    public function __construct(
        private readonly DataGridFactory      $dataGridFactory,
        private readonly DefaultEntityFactory $defaultEntityFactory,
        private readonly Role                 $roleModel,
        private readonly SubmenuFactory       $submenuFactory,
        private readonly FormFactory          $formFactory,
        private readonly RoleFactory          $roleFactory,
    )
    {
        parent::__construct();
    }

    protected function createComponentFormEdit():Form
    {
        $form = $this->formFactory->createEdit($this->role);
        $form->onSuccess[] = function(Form $form, EditData $data):void{
            try {
                $this->roleFactory->update($this->role, $data);
                $this->flashMessage($this->translator->translate('flash_roleCreated'));
                $this->redirect('default');
            }catch(SystematicRoleException $e){
                $this->flashMessage($this->translator->translate('flash_cannotUpdateSystematicRole'), 'error');
                $this->redirect('default');
            }
        };
        return $form;
    }

    private function exist(int $id):void{
        $role = $this->roleModel->get($id);
        if($role === null){
            $this->error($this->translator->translate('flash_roleNotFound'));
        }
        $this->role = $role;
    }

    public function actionEdit(int $id):void{
        $this->exist($id);
    }

    protected function createComponentFormNew():Form
    {
        $form = $this->formFactory->createNew();
        $form->onSuccess[] = function(Form $form, NewData $data):void{
            $this->roleFactory->create($data);
            $this->flashMessage($this->translator->translate('flash_roleCreated'));
            $this->redirect('default');
        };
        return $form;
    }

    protected function startup():void
    {
        parent::startup();
        $this->submenuFactory->addMenu($this->translator->translate('menu_newRole'), 'new')
            ->setIsPrimary()
            ->setModalId('new-role')
        ;
    }

    protected function createComponentGrid():DataGrid
    {
        return $this->dataGridFactory->create($this->roleModel->getToGrid(), $this->defaultEntityFactory->create());
    }
}