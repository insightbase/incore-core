<?php

namespace App\UI\Admin\Translate;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Admin\Translate;
use App\Model\Entity\TranslateEntity;
use App\Model\Enum\RoleEnum;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Admin\Submenu\SubmenuFactory;
use App\UI\Admin\Translate\DataGrid\DefaultDataGridEntityFactory;
use App\UI\Admin\Translate\Form\FormFactory;
use App\UI\Admin\Translate\Form\FormNewData;
use App\UI\Admin\Translate\Form\FormTranslateData;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;

class TranslatePresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    /**
     * @var ?TranslateEntity
     */
    private ?ActiveRow $translate;
    #[Persistent]
    public string $source = 'front';
    #[Persistent]
    public string $key = '';

    public function __construct(
        private readonly DataGridFactory $dataGridFactory,
        private readonly DefaultDataGridEntityFactory $defaultDataGridEntityFactory,
        private readonly Translate $translateModel,
        private readonly FormFactory $formFactory,
        private readonly TranslateFacade $translateFacade,
        private readonly SubmenuFactory $submenuFactory,
    ) {
        parent::__construct();
    }

    protected function createComponentFormNew():Form
    {
        $form = $this->formFactory->createNew();
        $form->onSuccess[] = function(Form $form, FormNewData $data):void{
            $this->translateFacade->create($data);
            $this->flashMessage($this->translator->translate('flash_keyCreated'));
            $this->redirect('default');
        };
        return $form;
    }

    protected function startup():void
    {
        parent::startup();

        if($this->getUser()->isInRole(RoleEnum::SUPER_ADMIN->value)) {
            $this->submenuFactory->addMenu($this->translator->translate('menu_admin'), 'default')
                ->addParam('source', 'admin')
                ->addParam('key', '')
                ->setShowInDropdown();
            $this->submenuFactory->addMenu($this->translator->translate('menu_synchronize'), 'synchronize')
                ->setShowInDropdown();
            $this->submenuFactory->addMenu($this->translator->translate('menu_new'), 'new')
                ->setShowInDropdown();
        }
    }

    #[NoReturn]
    public function actionSynchronize(): void
    {
        $this->translateFacade->synchronize();
        $this->flashMessage($this->translator->translate('flash_synchronizeComplete'));
        $this->redirect('default');
    }

    public function actionTranslate(int $id): void
    {
        $this->exist($id);
        $this->template->translate = $this->translate;
    }

    protected function createComponentFormTranslate(): Form
    {
        $form = $this->formFactory->createTranslate($this->translate);
        $form->onSuccess[] = function (Form $form, array $data): void {
            $this->translateFacade->translate($this->translate, $data);
            $this->flashMessage($this->translator->translate('flash_translateSet'));
            $this->redirect('default');
        };

        return $form;
    }

    protected function createComponentGrid(): DataGrid
    {
        return $this->dataGridFactory->create($this->translateModel->getToGrid($this->source, $this->key), $this->defaultDataGridEntityFactory->create());
    }

    private function exist(int $id): void
    {
        $translate = $this->translateModel->get($id);
        if (null === $translate) {
            $this->error($this->translator->translate('flash_keyNotFound'));
        }
        $this->translate = $translate;
    }
}
