<?php

namespace App\UI\Admin\StaticPage;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Admin\StaticPage;
use App\Model\Entity\StaticPageEntity;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Admin\Submenu\SubmenuFactory;
use App\UI\Admin\StaticPage\DataGrid\DefaultDataGridEntityFactory;
use App\UI\Admin\StaticPage\Form\FormFactory;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Presenter;

class StaticPagePresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    /**
     * @var StaticPageEntity
     */
    private \Nette\Database\Table\ActiveRow $staticPage;

    public function __construct(
        private readonly DefaultDataGridEntityFactory $defaultDataGridEntityFactory,
        private readonly DataGridFactory              $dataGridFactory,
        private readonly StaticPage                   $staticPageModel,
        private readonly SubmenuFactory               $submenuFactory,
        private readonly FormFactory                  $formFactory,
        private readonly StaticPageFacade             $staticPageFacade,
    )
    {
        parent::__construct();
    }

    #[NoReturn]
    public function actionDelete(int $id):void
    {
        $this->exist($id);
        $this->staticPageFacade->delete($this->staticPage);
        $this->flashMessage($this->translator->translate('flash_staticPageDeleted'));
        $this->redirect('default');
    }

    public function actionNew():void
    {
        $this->template->showH1 = false;
    }

    public function actionEdit(int $id):void
    {
        $this->exist($id);
        $this->template->showH1 = false;
    }

    protected function createComponentFormEdit():Form
    {
        $form = $this->formFactory->create($this->staticPage);
        $form->onSuccess[] = function():void{
            $this->flashMessage($this->translator->translate('flash_actionUpdated'));
            $this->redirect('this');
        };
        return  $form;
    }

    protected function createComponentFormNew():Form
    {
        $form = $this->formFactory->create();
        $form->onSuccess[] = function():void{
            $this->flashMessage($this->translator->translate('flash_actionCreated'));
            $this->redirect('default');
        };
        return  $form;
    }

    protected function createComponentGrid():DataGrid
    {
        return $this->dataGridFactory->create($this->staticPageModel->getToGrid(), $this->defaultDataGridEntityFactory->create());
    }

    public function actionDefault(): void
    {
        if($this->getUser()->isAllowed('staticPage', 'new')) {
            $this->submenuFactory->addMenu($this->translator->translate('menu_staticPageNew'), 'new')
                ->setIsPrimary()
                ->setIcon('ki-filled ki-plus')
            ;
        }
    }

    private function exist(int $id):void
    {
        $staticPage = $this->staticPageModel->get($id);
        if($staticPage === null){
            $this->flashMessage($this->translator->translate('flash_staticPageNotFound'), 'error');
            $this->redirect('default');
        }
        $this->staticPage = $staticPage;
    }
}