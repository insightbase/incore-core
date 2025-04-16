<?php

namespace App\UI\Admin\Email;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Admin\Email;
use App\Model\Entity\EmailEntity;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Admin\Submenu\SubmenuFactory;
use App\UI\Admin\Email\DataGrid\DataGridEntityFactory;
use App\UI\Admin\Email\Form\EditFormData;
use App\UI\Admin\Email\Form\FormFactory;
use App\UI\Admin\Email\Form\NewFormData;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;

class EmailPresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    /**
     * @var EmailEntity
     */
    private ActiveRow $email;

    public function __construct(
        private readonly Email                 $emailModel,
        private readonly DataGridEntityFactory $dataGridEntityFactory,
        private readonly DataGridFactory       $dataGridFactory,
        private readonly SubmenuFactory        $submenuFactory,
        private readonly FormFactory           $formFactory,
        private readonly EmailFacade           $emailFacade,
    )
    {
        parent::__construct();
    }

    #[NoReturn] public function actionDelete(int $id):void
    {
        $this->exist($id);
        $this->emailFacade->delete($this->email);
        $this->flashMessage($this->translator->translate('flash_emailDeleted'));
        $this->redirect('default');
    }

    protected function createComponentFormEdit():Form
    {
        $form = $this->formFactory->createEdit($this->email);
        $form->onSuccess[] = function(Form $form, EditFormData $data):void{
            $this->emailFacade->update($this->email, $data);
            $this->flashMessage($this->translator->translate('flash_emailUpdated'));
            $this->redirect('this');
        };
        return $form;
    }

    private function exist(int $id):void{
        $email = $this->emailModel->get($id);
        if($email === null){
            $this->error($this->translator->translate('flash_emailNotFound'));
        }
        $this->email = $email;
    }

    public function actionEdit(int $id):void
    {
        $this->exist($id);
    }

    protected function createComponentFormNew():Form
    {
        $form = $this->formFactory->createNew();
        $form->onSuccess[] = function(Form $form, NewFormData $data):void{
            $this->emailFacade->create($data);
            $this->flashMessage($this->translator->translate('flash_emailCreated'));
            $this->redirect('default');
        };
        return $form;
    }

    public function actionDefault():void
    {
        $this->submenuFactory->addMenu($this->translator->translate('menu_newEmail'), 'new')
            ->setIsPrimary()
            ->setModalId('formNew')
        ;
    }

    protected function createComponentGrid():DataGrid
    {
        return $this->dataGridFactory->create($this->emailModel->getToGrid(), $this->dataGridEntityFactory->create());
    }
}