<?php

namespace App\UI\Admin\Log;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Admin\Log;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Admin\Log\DataGrid\DefaultDataGridEntityFactory;
use Nette\Application\UI\Presenter;

class LogPresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    public function __construct(
        private readonly DefaultDataGridEntityFactory $defaultDataGridEntityFactory,
        private readonly DataGridFactory              $dataGridFactory,
        private readonly Log                          $logModel,
    )
    {
        parent::__construct();
    }

    public function createComponentGrid():DataGrid
    {
        return $this->dataGridFactory->create($this->logModel->getToGrid(), $this->defaultDataGridEntityFactory->create());
    }
}