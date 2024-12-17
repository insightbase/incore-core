<?php

namespace App\Core;

use App\Component\Translator\Translator;
use App\Model\User;
use Nette\Security\AuthenticationException;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

readonly class Authenticator implements \Nette\Security\Authenticator
{
    public function __construct(
        private User       $userModel,
        private Translator $translator,
        private Passwords  $passwords,
    )
    {
    }

    function authenticate(string $username, string $password): IIdentity
    {
        $user = $this->userModel->findByEmail($username);
        if($user === null) {
            throw new AuthenticationException($this->translator->translate('Uživatel nebyl nalezen.'));
        }
        if(!$this->passwords->verify($password, $user->password)){
            throw new AuthenticationException($this->translator->translate('Špatné heslo.'));
        }
        $userArray = $user->toArray();
        unset($userArray['password']);
        return new SimpleIdentity($user->id, ['user'], $userArray);
    }
}