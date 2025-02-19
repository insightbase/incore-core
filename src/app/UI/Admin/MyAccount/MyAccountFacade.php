<?php

namespace App\UI\Admin\MyAccount;

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
    ) {}

    public function changePassword(MyAccountChangePasswordData $data): void
    {
        $user = $this->userModel->get($this->userSecurity->getId());
        $user->update(['password' => $this->passwords->hash($data->password)]);
    }

    public function edit(FormData $data): void
    {
        $user = $this->userModel->get($this->userSecurity->getId());
        $user->update((array) $data);
    }
}
