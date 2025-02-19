<?php

namespace App\UI\Admin\Module;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Admin\Module;
use App\Model\Entity\ModuleEntity;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Admin\Module\DataGrid\DefaultDataGridEntityFactory;
use App\UI\Admin\Module\Form\EditData;
use App\UI\Admin\Module\Form\FormFactory;
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
        private readonly DataGridFactory $dataGridFactory,
        private readonly DefaultDataGridEntityFactory $defaultDataGridEntityFactory,
        private readonly Module $moduleModel,
        private readonly FormFactory $formFactory,
        private readonly ModuleFacade $moduleFacade,
    ) {
        parent::__construct();
    }

    public function actionEdit(int $id): void
    {
        $module = $this->moduleModel->get($id);
        if (!$module) {
            $this->error($this->translator->translate('flash_moduleNotFound'));
        }
        $this->module = $module;
    }

    protected function createComponentFormEdit(): Form
    {
        $form = $this->formFactory->createEdit($this->module);
        $form->onSuccess[] = function (Form $form, EditData $data): void {
            $this->moduleFacade->update($this->module, $data);
            $this->flashMessage($this->translator->translate('flash_module_updated'));
            $this->redirect('default');
        };

        return $form;
    }

    protected function createComponentGrid(): DataGrid
    {
        return $this->dataGridFactory->create($this->moduleModel->getToGrid(), $this->defaultDataGridEntityFactory->create());
    }
}
