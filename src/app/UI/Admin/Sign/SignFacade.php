<?php

namespace App\UI\Admin\Sign;

use App\Component\Mail\Exception\SystemNameNotFoundException;
use App\Component\Mail\SenderFactory;
use App\Component\Mail\SystemNameEnum;
use App\Model\Entity\UserEntity;
use App\UI\Admin\Sign\Exception\HashExpiredException;
use App\UI\Admin\Sign\Exception\UserNotFoundException;
use App\UI\Admin\Sign\Form\SignFormData;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\Table\ActiveRow;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;
use Nette\Security\User;
use Nette\Utils\DateTime;

readonly class SignFacade
{
    public function __construct(
        private User                  $userSecurity,
        private \App\Model\Admin\User $userModel,
        private Passwords             $passwords,
        private SenderFactory         $senderFactory,
        private LinkGenerator         $linkGenerator
    ) {}

    /**
     * @throws AuthenticationException
     */
    public function login(SignFormData $data): void
    {
        if ($data->rememberMe) {
            $this->userSecurity->setExpiration('14 days');
        }
        $this->userSecurity->login($data->email, $data->password);
    }

    /**
     * @return UserEntity
     *
     * @throws UserNotFoundException
     * @throws SystemNameNotFoundException
     * @throws \DateMalformedStringException
     * @throws InvalidLinkException
     */
    public function forgotPassword(\App\UI\Admin\Sign\Form\ForgotPasswordFormData $data): ActiveRow
    {
        $user = $this->userModel->findByEmail($data->email);
        if (null === $user) {
            throw new UserNotFoundException();
        }

        $expire = (new DateTime())->modify('+1 hour');
        $hash = $this->passwords->hash($user->id.'generatePasswordHash'.$expire->getTimestamp());

        $user->update([
            'forgot_password_hash' => $hash,
            'forgot_password_expire' => $expire,
        ]);

        $sender = $this->senderFactory->create(SystemNameEnum::ForgotPassword->value);
        $sender->addTo($user->email);
        $sender->addModifier('link', $this->linkGenerator->link('Admin:Sign:resetPassword', ['hash' => $hash]));
        $sender->send();

        return $user;
    }

    /**
     * @return UserEntity
     *
     * @throws HashExpiredException
     * @throws UserNotFoundException
     */
    public function checkForgotPasswordHash(string $hash): ActiveRow
    {
        $user = $this->userModel->findByHash($hash);
        if (null === $user) {
            throw new UserNotFoundException();
        }

        if ($user['forgot_password_expire'] < new DateTime()) {
            throw new HashExpiredException();
        }

        return $user;
    }

    /**
     * @throws HashExpiredException
     * @throws UserNotFoundException
     */
    public function resetPassword(string $hash, \App\UI\Admin\Sign\Form\ResetPasswordFormData $data): void
    {
        $user = $this->checkForgotPasswordHash($hash);
        $user->update([
            'password' => $this->passwords->hash($data->password),
            'forgot_password_hash' => null,
            'forgot_password_expire' => null,
        ]);
    }
}
