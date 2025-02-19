<?php

namespace App\UI\Admin\Role;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Admin\Module;
use App\Model\Admin\Role;
use App\Model\Entity\ModuleEntity;
use App\Model\Entity\RoleEntity;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Admin\Submenu\SubmenuFactory;
use App\UI\Admin\Role\DataGrid\DefaultEntityFactory;
use App\UI\Admin\Role\DataGrid\ModuleEntityFactory;
use App\UI\Admin\Role\Exception\SystematicRoleException;
use App\UI\Admin\Role\Form\AuthorizationSetData;
use App\UI\Admin\Role\Form\EditData;
use App\UI\Admin\Role\Form\FormFactory;
use App\UI\Admin\Role\Form\NewData;
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

    /**
     * @var ModuleEntity
     */
    private ActiveRow $module;

    public function __construct(
        private readonly DataGridFactory $dataGridFactory,
        private readonly DefaultEntityFactory $defaultEntityFactory,
        private readonly Role $roleModel,
        private readonly SubmenuFactory $submenuFactory,
        private readonly FormFactory $formFactory,
        private readonly RoleFacade $roleFacade,
        private readonly Module $moduleModel,
        private readonly ModuleEntityFactory $moduleEntityFactory,
    ) {
        parent::__construct();
    }

    public function actionSet(int $id, int $roleId): void
    {
        $this->exist($roleId);
        $this->existModule($id);
    }

    public function actionAuthorization(int $id): void
    {
        $this->exist($id);
    }

    public function actionEdit(int $id): void
    {
        $this->exist($id);
    }

    protected function createComponentFormAuthorizationSet(): Form
    {
        $form = $this->formFactory->createAuthorizationSet($this->role, $this->module);
        $form->onSuccess[] = function (Form $form, AuthorizationSetData $data): void {
            $this->roleFacade->setAuthorization($this->role, $this->module, $data);
            $this->flashMessage($this->translator->translate('flash_roleAuthorizationSet'));
            $this->redirect('authorization', $this->role->id);
        };

        return $form;
    }

    protected function createComponentGridModule(): DataGrid
    {
        return $this->dataGridFactory->create($this->moduleModel->getToGridAuthorizationSet($this->getUser()), $this->moduleEntityFactory->create($this->role));
    }

    protected function createComponentFormEdit(): Form
    {
        $form = $this->formFactory->createEdit($this->role);
        $form->onSuccess[] = function (Form $form, EditData $data): void {
            try {
                $this->roleFacade->update($this->role, $data);
                $this->flashMessage($this->translator->translate('flash_roleCreated'));
                $this->redirect('default');
            } catch (SystematicRoleException $e) {
                $this->flashMessage($this->translator->translate('flash_cannotUpdateSystematicRole'), 'error');
                $this->redirect('default');
            }
        };

        return $form;
    }

    protected function createComponentFormNew(): Form
    {
        $form = $this->formFactory->createNew();
        $form->onSuccess[] = function (Form $form, NewData $data): void {
            $this->roleFacade->create($data);
            $this->flashMessage($this->translator->translate('flash_roleCreated'));
            $this->redirect('default');
        };

        return $form;
    }

    protected function startup(): void
    {
        parent::startup();
        $this->submenuFactory->addMenu($this->translator->translate('menu_newRole'), 'new')
            ->setIsPrimary()
            ->setModalId('new-role')
        ;
    }

    protected function createComponentGrid(): DataGrid
    {
        return $this->dataGridFactory->create($this->roleModel->getToGrid(), $this->defaultEntityFactory->create());
    }

    private function existModule(int $id): void
    {
        $module = $this->moduleModel->get($id);
        if (null === $module) {
            $this->error($this->translator->translate('flash_moduleNotFound'));
        }
        $this->module = $module;
    }

    private function exist(int $id): void
    {
        $role = $this->roleModel->get($id);
        if (null === $role) {
            $this->error($this->translator->translate('flash_roleNotFound'));
        }
        $this->role = $role;
    }
}
