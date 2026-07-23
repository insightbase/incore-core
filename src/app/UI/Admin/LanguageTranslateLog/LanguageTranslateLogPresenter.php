<?php

namespace App\UI\Admin\LanguageTranslateLog;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Model\Admin\LanguageTranslate;
use App\Model\Entity\LanguageTranslateEntity;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Admin\Submenu\SubmenuFactory;
use App\UI\Admin\LanguageTranslateLog\DataGrid\LanguageTranslateLogDataGridEntityFactory;
use Nette\Application\UI\Presenter;
use Nette\Utils\Json;

class LanguageTranslateLogPresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;

    public function __construct(
        private readonly LanguageTranslate $languageTranslateModel,
        private readonly LanguageTranslateLogDataGridEntityFactory $dataGridEntityFactory,
        private readonly DataGridFactory $dataGridFactory,
        private readonly SubmenuFactory $submenuFactory,
    ) {
        parent::__construct();
    }

    public function actionDefault(): void
    {
        $this->submenuFactory->addMenu($this->translator->translate('menu_languageTranslateLog_markAllFinished'), 'markAllFinished');
    }

    public function actionMarkAllFinished(): void
    {
        $count = $this->languageTranslateModel->markAllUnfinished(new \DateTime());
        $this->flashMessage($this->translator->translate('flash_languageTranslateLog_marked', ['count' => $count]));
        $this->redirect('default');
    }

    public function actionDetail(int $id): void
    {
        /** @var ?LanguageTranslateEntity $record */
        $record = $this->languageTranslateModel->getTable()->get($id);
        if (null === $record) {
            $this->error();
        }

        $request = $record->request;
        try {
            // Čitelně naformátovat, když je to validní JSON; jinak nechat raw.
            $request = Json::encode(Json::decode($request), pretty: true);
        } catch (\Throwable) {
        }

        $language = $record->ref('language', 'language_id');

        $this->template->request = $request;
        $this->template->datetime = $record->datetime;
        $this->template->finished = $record->finished;
        $this->template->dropCoreId = $record->drop_core_id;
        $this->template->languageName = null === $language ? '' : (string) $language['name'];
    }

    protected function createComponentGrid(): DataGrid
    {
        return $this->dataGridFactory->create(
            $this->languageTranslateModel->getToGrid(),
            $this->dataGridEntityFactory->create(),
        );
    }
}
