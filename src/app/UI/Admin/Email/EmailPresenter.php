<?php

namespace App\UI\Admin\Email;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Admin\Email;
use App\Model\Admin\EmailLog;
use App\Model\Entity\EmailEntity;
use App\Model\Entity\EmailLogEntity;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Admin\Submenu\SubmenuFactory;
use App\UI\Admin\Email\DataGrid\DataGridEntityFactory;
use App\UI\Admin\Email\DataGrid\DataGridLogEntityFactory;
use App\UI\Admin\Email\Form\EditFormData;
use App\UI\Admin\Email\Form\FormFactory;
use App\UI\Admin\Email\Form\NewFormData;
use App\UI\Admin\Language\Exception\BasicAuthNotSetException;
use App\UI\Admin\Language\Exception\NotEnoughCreditsException;
use App\UI\Admin\Language\Exception\TranslateApiException;
use App\UI\Admin\Language\LanguageFacade;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;

/**
 * @property-read Template $template
 */
class EmailPresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    /**
     * @var EmailEntity
     */
    private ActiveRow $email;
    /**
     * @var EmailLogEntity
     */
    private ActiveRow $emailLog;

    public function __construct(
        private readonly Email                    $emailModel,
        private readonly DataGridEntityFactory    $dataGridEntityFactory,
        private readonly DataGridFactory          $dataGridFactory,
        private readonly SubmenuFactory           $submenuFactory,
        private readonly FormFactory              $formFactory,
        private readonly EmailFacade              $emailFacade,
        private readonly DataGridLogEntityFactory $dataGridLogEntityFactory,
        private readonly EmailLog                 $emailLogModel,
        private readonly LanguageFacade           $languageFacade,
    )
    {
        parent::__construct();
    }

    #[NoReturn] public function actionDelete(int $id):void
    {
        $this->exist($id);
        $this->emailFacade->delete($this->email);
        $this->flashMessage($this->translator->translate('flash_emailDeleted'));
        $this->redirect('list');
    }

    protected function createComponentFormEdit():Form
    {
        $form = $this->formFactory->createEdit($this->email);
        $form->onSuccess[] = function(Form $form, EditFormData $data):void{
            $this->emailFacade->update($this->email, $data, $form);
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

    private function existLog(int $id):void{
        $emailLog = $this->emailLogModel->get($id);
        if($emailLog === null){
            $this->error($this->translator->translate('flash_emailLogNotFound'));
        }
        $this->emailLog = $emailLog;
    }

    public function actionShow(int $id):void
    {
        $this->existLog($id);
        $this->template->emailLog = $this->emailLog;
    }

    public function actionEdit(int $id):void
    {
        $this->exist($id);
        $this->submenuFactory->addMenu($this->translator->translate('menu_translateEmail'), 'translate')
            ->addParam('id', (string)$id)
        ;
    }

    #[NoReturn] public function actionTranslate(int $id):void
    {
        $this->exist($id);
        try {
            foreach($this->languageModel->getToTranslateNotDefault() as $language) {
                $this->languageFacade->translateProviderItem('email', $this->email->id, $language);
            }
        } catch (BasicAuthNotSetException $e) {
            $this->flashMessage($this->translator->translate('flash_basicAuthNotSet'), 'error');
            $this->redirect('edit', ['id' => $id]);
        } catch (NotEnoughCreditsException $e) {
            $this->flashMessage($this->translator->translate('flash_notEnoughCredits'), 'error');
            // Bez práv na kredity by uživatel skončil na chybové stránce, proto ho necháme v detailu.
            if($this->getUser()->isAllowed('credit', 'default')){
                $this->redirect('Credit:default');
            }
            $this->redirect('edit', ['id' => $id]);
        } catch (TranslateApiException $e) {
            $this->flashMessage($this->translator->translate('flash_translateApiError'), 'error');
            $this->redirect('edit', ['id' => $id]);
        }
        $this->flashMessage($this->translator->translate('flash_emailSendToTranslate'));
        $this->redirect('edit', ['id' => $id]);
    }

    protected function createComponentFormNew():Form
    {
        $form = $this->formFactory->createNew();
        $form->onSuccess[] = function(Form $form, NewFormData $data):void{
            $this->emailFacade->create($data, $form);
            $this->flashMessage($this->translator->translate('flash_emailCreated'));
            $this->redirect('list');
        };
        return $form;
    }

    public function actionList():void
    {
        $this->submenuFactory->addMenu($this->translator->translate('menu_newEmail'), 'new')
            ->setIsPrimary()
            ->setModalId('formNew')
        ;
    }

    public function actionDefault():void
    {
        $this->submenuFactory->addMenu($this->translator->translate('menu_list'), 'list')
            ->setShowInDropdown()
        ;
    }

    protected function createComponentGridLog():DataGrid
    {
        return $this->dataGridFactory->create($this->emailLogModel->getToGrid(), $this->dataGridLogEntityFactory->create());
    }

    protected function createComponentGrid():DataGrid
    {
        return $this->dataGridFactory->create($this->emailModel->getToGrid(), $this->dataGridEntityFactory->create());
    }
}