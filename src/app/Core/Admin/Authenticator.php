<?php

namespace App\Core\Admin;

use App\Component\Translator\Translator;
use App\Model\Admin\User;
use Nette\Security\AuthenticationException;
use Nette\Security\IdentityHandler;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

readonly class Authenticator implements \Nette\Security\Authenticator, IdentityHandler
{
    public function __construct(
        private User $userModel,
        private Translator $translator,
        private Passwords $passwords,
    ) {}

    public function wakeupIdentity(IIdentity $identity): ?IIdentity
    {
        $user = $this->userModel->get($identity->getId());
        $userArray = $user->toArray();
        unset($userArray['password']);

        return new SimpleIdentity($user->id, $user->ref('role', 'role_id')['system_name'], $userArray);
    }

    public function sleepIdentity(IIdentity $identity): IIdentity
    {
        return $identity;
    }

    public function authenticate(string $username, string $password): IIdentity
    {
        $user = $this->userModel->findByEmail($username);
        if (null === $user) {
            throw new AuthenticationException($this->translator->translate('flash_userNotFound'));
        }
        if (!$this->passwords->verify($password, $user->password)) {
            throw new AuthenticationException($this->translator->translate('flash_badPassword'));
        }
        $userArray = $user->toArray();
        unset($userArray['password']);

        return new SimpleIdentity($user->id, $user->ref('role', 'role_id')['system_name'], $userArray);
    }
}
