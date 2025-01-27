<?php

namespace App\UI\Sign;

use App\UI\Accessory\Form\Form;
use App\UI\Accessory\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\PresenterTrait\StoreRequestTrait;
use App\UI\Sign\Form\ForgotPasswordFormData;
use App\UI\Sign\Form\ResetPasswordFormData;
use App\UI\Sign\Form\SignFormData;
use App\UI\Sign\Form\SignFormFactory;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Presenter;
use Nette\Security\AuthenticationException;

/**
 * @property SignTemplate $template
 */
class SignPresenter extends Presenter
{
    use StandardTemplateTrait;
    use StoreRequestTrait;

    private string $hash;

    public function __construct(
        private readonly SignFormFactory $signFormFactory,
        private readonly SignFacade $signFacade,
    ) {
        parent::__construct();
    }

    public function actionResetPassword(string $hash): void
    {
        try {
            $this->signFacade->checkForgotPasswordHash($hash);
        } catch (Exception\HashExpiredException $e) {
            $this->flashMessage($this->translator->translate('flash_hasNotFound'), 'error');
            $this->redirect('forgotPassword');
        } catch (Exception\UserNotFoundException $e) {
            $this->flashMessage($this->translator->translate('flash_linkNotValid'), 'error');
            $this->redirect('forgotPassword');
        }
        $this->hash = $hash;
    }

    public function actionCheckEmail(string $email): void
    {
        $this->template->email = $email;
    }

    #[NoReturn]
    public function actionLogout(): void
    {
        $this->getUser()->logout();
        $this->flashMessage($this->translator->translate('flash_userLoggedOut'));
        $this->redirect('Sign:login');
    }

    protected function startup(): void
    {
        parent::startup();
        if ($this->getUser()->isLoggedIn() && 'logout' !== $this->getAction()) {
            $this->redirect('Home:default');
        }
    }

    protected function createComponentFormLogin(): Form
    {
        $form = $this->signFormFactory->create();
        $form->onSuccess[] = function (Form $form, SignFormData $data): void {
            try {
                $this->signFacade->login($data);
            } catch (AuthenticationException $e) {
                $this->flashMessage($e->getMessage(), 'error');
            }
            $this->restoreRequest($this->storeRequest);
            $this->redirect('Home:default');
        };

        return $form;
    }

    protected function createComponentFormForgotPassword(): Form
    {
        $form = $this->signFormFactory->createForgotPassword();
        $form->onSuccess[] = function (Form $form, ForgotPasswordFormData $data): void {
            $user = $this->signFacade->forgotPassword($data);
            $this->redirect('checkEmail', $user->email);
        };

        return $form;
    }

    protected function createComponentFormResetPassword(): Form
    {
        $form = $this->signFormFactory->createResetPassword();
        $form->onSuccess[] = function (Form $form, ResetPasswordFormData $data): void {
            try {
                $this->signFacade->resetPassword($this->hash, $data);
            } catch (Exception\HashExpiredException $e) {
                $this->flashMessage($this->translator->translate('flash_hasNotFound'), 'error');
                $this->redirect('forgotPassword');
            } catch (Exception\UserNotFoundException $e) {
                $this->flashMessage($this->translator->translate('flash_linkNotValid'), 'error');
                $this->redirect('forgotPassword');
            }
            $this->redirect('passwordChanged');
        };

        return $form;
    }
}
