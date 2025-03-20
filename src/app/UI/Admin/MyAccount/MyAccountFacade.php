<?php

namespace App\UI\Admin\MyAccount;

use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Model\Admin\User;
use App\UI\Admin\MyAccount\Form\FormData;
use App\UI\Admin\MyAccount\Form\MyAccountChangePasswordData;
use Nette\Security\Passwords;

readonly class MyAccountFacade
{
    public function __construct(
        private User $userModel,
        private \Nette\Security\User $userSecurity,
        private Passwords $passwords,
        private LogFacade $logFacade,
    ) {}

    public function changePassword(MyAccountChangePasswordData $data): void
    {
        $user = $this->userModel->get($this->userSecurity->getId());
        $user->update(['password' => $this->passwords->hash($data->password)]);
        $this->logFacade->create(LogActionEnum::ChangePassword, 'user', $user->id);
    }

    public function edit(FormData $data): void
    {
        $user = $this->userModel->get($this->userSecurity->getId());
        $user->update((array) $data);
        $this->logFacade->create(LogActionEnum::Updated, 'user', $user->id);
    }
}
