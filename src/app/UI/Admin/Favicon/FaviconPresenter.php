<?php

namespace App\UI\Admin\Favicon;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Admin\Favicon;
use App\Model\Entity\FaviconEntity;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Admin\Submenu\SubmenuFactory;
use App\UI\Admin\Favicon\DataGrid\DataGridEntityFactory;
use App\UI\Admin\Favicon\Exception\NotFoundFilesException;
use App\UI\Admin\Favicon\Form\FormEditData;
use App\UI\Admin\Favicon\Form\FormFactory;
use App\UI\Admin\Favicon\Form\FormImportData;
use App\UI\Admin\Favicon\Form\FormNewData;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class FaviconPresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    /**
     * @var FaviconEntity
     */
    private ActiveRow $favicon;

    public function __construct(
        private readonly DataGridFactory       $dataGridFactory,
        private readonly DataGridEntityFactory $dataGridEntityFactory,
        private readonly Favicon               $faviconModel,
        private readonly SubmenuFactory        $submenuFactory,
        private readonly FormFactory           $formFactory,
        private readonly FaviconFacade         $faviconFacade,
    )
    {
        parent::__construct();
    }

    protected function createComponentFormImport():Form
    {
        $form = $this->formFactory->createImport();
        $form->onSuccess[] = function(Form $form, FormImportData $data):void{
            try {
                $this->faviconFacade->import($data);
            }catch(NotFoundFilesException $e){
                $this->flashMessage($e->getMessage(), 'error');
            }
            $this->flashMessage($this->translator->translate('flash_faviconImported'));
            $this->redirect('this');
        };
        return $form;
    }

    protected function createComponentFormEdit():Form
    {
        $form = $this->formFactory->createEdit($this->favicon);
        $form->onSuccess[] = function(Form $form, FormEditData $data):void{
            $this->faviconFacade->update($this->favicon, $data);
            $this->flashMessage($this->translator->translate('flash_faviconUpdated'));
            $this->redirect('this');
        };
        return $form;
    }

    private function exist(int $id):void{
        $favicon = $this->faviconModel->get($id);
        if (null === $favicon) {
            $this->error($this->translator->translate('flash_faviconNotFound'));
        }
        $this->favicon = $favicon;
    }

    public function actionEdit(int $id):void
    {
        $this->exist($id);
    }

    #[NoReturn] public function actionDelete(int $id):void
    {
        $this->exist($id);
        $this->faviconFacade->delete($this->favicon);
        $this->flashMessage($this->translator->translate('flash_faviconDeleted'));
        $this->redirect('default');
    }

    protected function createComponentFormNew():Form
    {
        $form = $this->formFactory->createNew();
        $form->onSuccess[] = function(Form $form, FormNewData $data):void{
            $this->faviconFacade->create($data);
            $this->flashMessage($this->translator->translate('flash_faviconCreated'));
            $this->redirect('default');
        };
        return $form;
    }

    protected function createComponentGrid():DataGrid
    {
        return $this->dataGridFactory->create($this->faviconModel->getToGrid(), $this->dataGridEntityFactory->create());
    }

    public function actionDefault():void
    {
        $this->submenuFactory->addMenu($this->translator->translate('menu_new'), 'new')
            ->setIsPrimary()
            ->setModalId('formNew')
        ;
        $this->submenuFactory->addMenu($this->translator->translate('menu_import'), 'import')
            ->setIsPrimary()
            ->setModalId('formImport')
        ;
    }
}