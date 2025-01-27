<?php

namespace App\UI\MyAccount;

use App\Model\User;
use App\UI\MyAccount\Form\FormData;
use App\UI\MyAccount\Form\MyAccountChangePasswordData;
use Nette\Security\Passwords;

class MyAccountFacade
{
    public function __construct(
        private User $userModel,
        private \Nette\Security\User $userSecurity,
        private Passwords $passwords,
    ) {}

    public function chnagePassword(MyAccountChangePasswordData $data): void
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
