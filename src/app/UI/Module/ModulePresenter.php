<?php

namespace App\UI\Module;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Entity\ModuleEntity;
use App\Model\Module;
use App\UI\Accessory\Form\Form;
use App\UI\Accessory\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\PresenterTrait\StandardTemplateTrait;
use App\UI\Module\DataGrid\DefaultDataGridEntityFactory;
use App\UI\Module\Form\EditData;
use App\UI\Module\Form\FormFactory;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;

class ModulePresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    /**
     * @var ?ModuleEntity
     */
    private ?ActiveRow $module;

    public function __construct(
        private readonly DataGridFactory              $dataGridFactory,
        private readonly DefaultDataGridEntityFactory $defaultDataGridEntityFactory,
        private readonly Module                       $moduleModel,
        private readonly FormFactory                  $formFactory,
        private readonly ModuleFacade                 $moduleFacade,
    )
    {
        parent::__construct();
    }

    protected function createComponentFormEdit():Form{
        $form = $this->formFactory->createEdit($this->module);
        $form->onSuccess[] = function(Form $form, EditData $data):void{
            $this->moduleFacade->update($this->module, $data);
            $this->flashMessage($this->translator->translate('flash_module_updated'));
            $this->redirect('default');
        };
        return $form;
    }

    public function actionEdit(int $id):void
    {
        $module = $this->moduleModel->get($id);
        if(!$module){
            $this->error($this->translator->translate('flash_moduleNotFound'));
        }
        $this->module = $module;
    }

    protected function createComponentGrid():DataGrid{
        return $this->dataGridFactory->create($this->moduleModel->getToGrid(), $this->defaultDataGridEntityFactory->create());
    }
}