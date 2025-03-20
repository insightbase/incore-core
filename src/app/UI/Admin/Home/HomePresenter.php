<?php

declare(strict_types=1);

namespace App\UI\Admin\Home;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Admin\Log;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Admin\Home\DataGrid\LogDataGridEntityFactory;
use Nette;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    public function __construct(
        private readonly Log                      $logModel,
        private readonly LogDataGridEntityFactory $logDataGridEntityFactory,
        private readonly DataGridFactory          $dataGridFactory,
    )
    {
        parent::__construct();
    }

    protected  function createComponentGridLog():DataGrid{
        return $this->dataGridFactory->create($this->logModel->getToGrid(), $this->logDataGridEntityFactory->create());
    }
}
