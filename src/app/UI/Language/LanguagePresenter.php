<?php

namespace App\UI\Language;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Language;
use App\UI\Accessory\Form\Form;
use App\UI\Accessory\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Submenu\SubmenuFactory;
use App\UI\Language\DataGrid\DefaultDataGridEntityFactory;
use App\UI\Language\Form\EditFormData;
use App\UI\Language\Form\FormFactory;
use App\UI\Language\Form\NewFormData;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;

class LanguagePresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    private ActiveRow $language;

    public function __construct(
        private readonly DefaultDataGridEntityFactory $defaultDataGridEntityFactory,
        private readonly FormFactory                  $formFactory,
        private readonly DataGridFactory              $dataGridFactory,
        private readonly Language                     $languageModel,
        private readonly SubmenuFactory               $submenuFactory,
        private readonly LanguageFacade               $languageFacade,
    )
    {
        parent::__construct();
    }

    private function exist(int $id):void{
        $language = $this->languageModel->get($id);
        if($language === null){
            $this->error($this->translator->translate('flash_languageNotFound'));
        }
        $this->language = $language;
    }

    public function actionEdit(int $id):void
    {
        $this->exist($id);
    }

    #[NoReturn] public function actionDelete(int $id):void
    {
        $this->exist($id);
        $this->languageFacade->delete($this->language);
        $this->flashMessage($this->translator->translate('flash_languageDeleted'));
        $this->redirect('default');
    }

    public function actionDefault():void
    {
        $this->submenuFactory->addMenu($this->translator->translate('menu_newLanguage'), 'new')
            ->setIsPrimary()
            ->setModalId('formNew')
        ;

    }

    protected function createComponentFormEdit():Form{
        $form = $this->formFactory->createEdit($this->language);
        $form->onSuccess[] = function(Form $form, EditFormData $data):void{
            $this->languageFacade->update($this->language, $data);
            $this->flashMessage($this->translator->translate('flash_languageUpdated'));
            $this->redirect('default');
        };
        return $form;
    }

    protected function createComponentFormNew():Form{
        $form = $this->formFactory->createNew();
        $form->onSuccess[] = function(Form $form, NewFormData $data):void{
            $this->languageFacade->create($data);
            $this->flashMessage($this->translator->translate('flash_languageCreated'));
            $this->redirect('default');
        };
        return $form;
    }

    protected function createComponentGrid():DataGrid{
        return $this->dataGridFactory->create($this->languageModel->getToGrid(), $this->defaultDataGridEntityFactory->create());
    }
}