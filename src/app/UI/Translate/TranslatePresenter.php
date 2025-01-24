<?php

namespace App\UI\Translate;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Entity\TranslateEntity;
use App\Model\Translate;
use App\UI\Accessory\Form\Form;
use App\UI\Accessory\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Submenu\SubmenuFactory;
use App\UI\Translate\DataGrid\DefaultDataGridEntityFactory;
use App\UI\Translate\Form\FormFactory;
use App\UI\Translate\Form\FormTranslateData;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Presenter;

class TranslatePresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    /**
     * @var ?TranslateEntity
     */
    private ?\Nette\Database\Table\ActiveRow $translate;

    public function __construct(
        private readonly DataGridFactory              $dataGridFactory,
        private readonly DefaultDataGridEntityFactory $defaultDataGridEntityFactory,
        private readonly Translate                    $translateModel,
        private readonly FormFactory                  $formFactory,
        private readonly TranslateFacade              $translateFacade,
        private readonly SubmenuFactory               $submenuFactory,
    )
    {
        parent::__construct();
    }

    #[NoReturn] public function actionSynchronize():void
    {
        $this->translateFacade->synchronize();
        $this->flashMessage($this->translator->translate('flash_synchronizeComplete'));
        $this->redirect('default');
    }

    public function actionDefault():void
    {
        $this->submenuFactory->addMenu($this->translator->translate('menu_synchronize'), 'synchronize')
            ->setIsPrimary();
    }

    protected function createComponentFormTranslate():Form{
        $form = $this->formFactory->createTranslate($this->translate);
        $form->onSuccess[] = function(Form $form, FormTranslateData $data):void{
            $this->translateFacade->translate($this->translate, $data);
            $this->flashMessage($this->translator->translate('flash_translateSet'));
            $this->redirect('default');
        };
        return $form;
    }

    private function exist(int $id):void{
        $translate = $this->translateModel->get($id);
        if($translate === null){
            $this->error($this->translator->translate('flash_keyNotFound'));
        }
        $this->translate = $translate;
    }

    public function actionTranslate(int $id):void
    {
        $this->exist($id);
        $this->template->translate = $this->translate;
    }

    protected function createComponentGrid():DataGrid
    {
        return $this->dataGridFactory->create($this->translateModel->getToGrid(), $this->defaultDataGridEntityFactory->create());
    }
}