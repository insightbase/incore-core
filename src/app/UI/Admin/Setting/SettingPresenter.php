<?php

namespace App\UI\Admin\Setting;

use App\Model\Admin\Setting;
use App\Model\Entity\SettingEntity;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Admin\Submenu\SubmenuFactory;
use App\UI\Admin\Setting\Form\AnalyticsFormData;
use App\UI\Admin\Setting\Form\EditFormData;
use App\UI\Admin\Setting\Form\FormFactory;
use App\UI\Admin\Setting\Form\TestEmailFormData;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;
use Nette\Mail\SendException;

class SettingPresenter extends Presenter
{
    use RequireLoggedUserTrait;
    use StandardTemplateTrait;

    /**
     * @var ?SettingEntity
     */
    private ?ActiveRow $setting;

    public function __construct(
        private readonly FormFactory $formFactory,
        private readonly SettingFacade $settingFacade,
        private readonly Setting $settingModel,
        private readonly SubmenuFactory $submenuFactory,
    ) {
        parent::__construct();
    }

    protected function createComponentFormAnalytics():Form
    {
        $form = $this->formFactory->createAnalytics($this->setting);
        $form->onSuccess[] = function(Form $form, AnalyticsFormData $data):void{
            $this->settingFacade->updateAnalytics($this->setting, $data);
            $this->flashMessage($this->translator->translate('flash_setting_analyticsUpdated'));
            $this->redirect('this');
        };
        return $form;
    }

    protected function createComponentFormTestEmail(): Form
    {
        $form = $this->formFactory->createTestEmail();

        $form->onSuccess[] = function (Form $form, TestEmailFormData $data): void {
            try {
                $this->settingFacade->testEmail($data);
                $this->flashMessage($this->translator->translate('flash_email_sended'));
            } catch (SendException $e) {
                $this->flashMessage($e->getMessage(), 'error');
            }
            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentFormEdit(): Form
    {
        $form = $this->formFactory->createEdit($this->setting);

        $form->onSuccess[] = function (Form $form, EditFormData $data): void {
            $this->settingFacade->update($this->setting, $data);
            $this->flashMessage($this->translator->translate('flash_setting_updated'));
            $this->redirect('this');
        };

        return $form;
    }

    protected function startup(): void
    {
        parent::startup();
        $this->submenuFactory->addMenu($this->translator->translate('menu_send_test_email'), 'testEmail')
            ->setIsPrimary()
        ;
        $this->setting = $this->settingModel->getDefault();
    }
}
