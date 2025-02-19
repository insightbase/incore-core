<?php

namespace App\UI\Admin\MyAccount;

use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Admin\MyAccount\Form\FormData;
use App\UI\Admin\MyAccount\Form\MyAccountChangePasswordData;
use App\UI\Admin\MyAccount\Form\MyAccountFormFactory;
use Nette\Application\UI\Presenter;

class MyAccountPresenter extends Presenter
{
    use RequireLoggedUserTrait;
    use StandardTemplateTrait;

    public function __construct(
        private readonly MyAccountFormFactory $myAccountFormFactory,
        private readonly MyAccountFacade $myAccountFacade,
    ) {
        parent::__construct();
    }

    protected function createComponentFormChangePassword(): Form
    {
        $form = $this->myAccountFormFactory->createChangePassword();
        $form->onSuccess[] = function (Form $form, MyAccountChangePasswordData $data): void {
            $this->myAccountFacade->changePassword($data);
            $this->flashMessage($this->translator->translate('flash_passwordChanged'));
            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentFormEdit(): Form
    {
        $form = $this->myAccountFormFactory->create();
        $form->onSuccess[] = function (Form $form, FormData $data): void {
            $this->myAccountFacade->edit($data);
            $this->flashMessage($this->translator->translate('flash_updated'));
            $this->redirect('this');
        };

        return $form;
    }
}
