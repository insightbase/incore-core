<?php

namespace App\UI\Admin\Language;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Admin\Language;
use App\Model\DoctrineEntity\LanguageSetting;
use App\Model\Entity\LanguageEntity;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Admin\Submenu\SubmenuFactory;
use App\UI\Admin\Language\DataGrid\DefaultDataGridEntityFactory;
use App\UI\Admin\Language\Exception\BasicAuthNotSetException;
use App\UI\Admin\Language\Form\EditFormData;
use App\UI\Admin\Language\Form\FormFactory;
use App\UI\Admin\Language\Form\LanguageSettingFormData;
use App\UI\Admin\Language\Form\NewFormData;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;

class LanguagePresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    /**
     * @var LanguageEntity
     */
    private ActiveRow $language;

    public function __construct(
        private readonly DefaultDataGridEntityFactory     $defaultDataGridEntityFactory,
        private readonly FormFactory                      $formFactory,
        private readonly DataGridFactory                  $dataGridFactory,
        private readonly Language                         $languageModel,
        private readonly SubmenuFactory                   $submenuFactory,
        private readonly LanguageFacade                   $languageFacade,
        private readonly \App\Model\Admin\LanguageSetting $languageSettingModel,
        private readonly LanguageSettingFacade            $languageSettingFacade,
    ) {
        parent::__construct();
    }

    #[NoReturn] public function actionTranslate(int $id):void
    {
        $this->exist($id);
        if($this->language->is_default){
            $this->flashMessage($this->translator->translate('flash_languageIsDefault'), 'error');
            $this->redirect('default');
        }
        try {
            $this->languageFacade->translate($this->language);
        } catch (Exception\TranslateInProgressException $e) {
            $this->flashMessage($this->translator->translate('flash_translationInProgress'));
            $this->redirect('default');
        } catch(BasicAuthNotSetException $e){
            $this->flashMessage($this->translator->translate('flash_basicAuthNotSet'), 'error');
            $this->redirect('Setting:default');
        }
        $this->flashMessage($this->translator->translate('flash_translationInProgress'));
        $this->redirect('default');
    }

    public function actionEdit(int $id): void
    {
        $this->exist($id);
    }

    #[NoReturn]
    public function actionDelete(int $id): void
    {
        $this->exist($id);
        $this->languageFacade->delete($this->language);
        $this->flashMessage($this->translator->translate('flash_languageDeleted'));
        $this->redirect('default');
    }

    protected function createComponentFormSetting():Form{
        $form = $this->formFactory->createSetting($this->languageSettingModel->getSetting());
        $form->onSuccess[] = function(Form $form, LanguageSettingFormData $data):void{
            $this->languageSettingFacade->update($this->languageSettingModel->getSetting(), $data);
            $this->flashMessage($this->translator->translate('flash_languageSettingUpdated'));
            $this->redirect('this');
        };
        return $form;
    }

    public function actionDefault(): void
    {
        $this->submenuFactory->addMenu($this->translator->translate('menu_setting'), 'setting');
        $this->submenuFactory->addMenu($this->translator->translate('menu_newLanguage'), 'new')
            ->setIsPrimary()
            ->setModalId('formNew')
        ;
    }

    protected function createComponentFormEdit(): Form
    {
        $form = $this->formFactory->createEdit($this->language);
        $form->onSuccess[] = function (Form $form, EditFormData $data): void {
            $this->languageFacade->update($this->language, $data);
            $this->flashMessage($this->translator->translate('flash_languageUpdated'));
            $this->redirect('default');
        };

        return $form;
    }

    protected function createComponentFormNew(): Form
    {
        $form = $this->formFactory->createNew();
        $form->onSuccess[] = function (Form $form, NewFormData $data): void {
            $this->languageFacade->create($data);
            $this->flashMessage($this->translator->translate('flash_languageCreated'));
            $this->redirect('default');
        };

        return $form;
    }

    protected function createComponentGrid(): DataGrid
    {
        return $this->dataGridFactory->create($this->languageModel->getToGrid(), $this->defaultDataGridEntityFactory->create());
    }

    private function exist(int $id): void
    {
        $language = $this->languageModel->get($id);
        if (null === $language) {
            $this->error($this->translator->translate('flash_languageNotFound'));
        }
        $this->language = $language;
    }
}
