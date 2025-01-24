<?php

namespace App\UI\Sign;

use App\Component\Mail\Sender;
use App\Component\Mail\SenderFactory;
use App\Component\Mail\SystemNameEnum;
use App\Core\Authenticator;
use App\Model\Entity\UserEntity;
use App\UI\Sign\Exception\HashExpiredException;
use App\UI\Sign\Exception\UserNotFoundException;
use App\UI\Sign\Form\SignFormData;
use Nette\Application\LinkGenerator;
use Nette\Database\Table\ActiveRow;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;
use Nette\Security\User;
use Nette\Utils\DateTime;

readonly class SignFacade
{
    public function __construct(
        private User $userSecurity,
        private \App\Model\User $userModel,
        private Passwords $passwords,
        private SenderFactory $senderFactory,
        private LinkGenerator $linkGenerator
    )
    {
    }

    /**
     * @param SignFormData $data
     * @return void
     * @throws AuthenticationException
     */
    public function login(SignFormData $data):void
    {
        if($data->rememberMe){
            $this->userSecurity->setExpiration('14 days');
        }
        $this->userSecurity->login($data->email, $data->password);
    }

    /**
     * @param Form\ForgotPasswordFormData $data
     * @return UserEntity
     * @throws UserNotFoundException
     * @throws \App\Component\Mail\Exception\SystemNameNotFoundException
     * @throws \DateMalformedStringException
     * @throws \Nette\Application\UI\InvalidLinkException
     */
    public function forgotPassword(Form\ForgotPasswordFormData $data):ActiveRow
    {
        $user = $this->userModel->findByEmail($data->email);
        if($user === null){
            throw new UserNotFoundException();
        }

        $expire = (new DateTime())->modify('+1 hour');
        $hash = $this->passwords->hash($user->id . 'generatePasswordHash' . $expire->getTimestamp());

        $user->update([
            'forgot_password_hash' => $hash,
            'forgot_password_expire' => $expire,
        ]);

        $sender = $this->senderFactory->create(SystemNameEnum::ForgotPassword->value);
        $sender->addTo($user->email);
        $sender->addModifier('link', $this->linkGenerator->link('Sign:resetPassword', ['hash' => $hash]));
        $sender->send();

        return $user;
    }

    /**
     * @param string $hash
     * @return UserEntity
     * @throws HashExpiredException
     * @throws UserNotFoundException
     */
    public function checkForgotPasswordHash(string $hash):ActiveRow
    {
        $user = $this->userModel->findByHash($hash);
        if($user === null){
            throw new UserNotFoundException();
        }

        if($user['forgot_password_expire'] < new DateTime()){
            throw new HashExpiredException();
        }

        return $user;
    }

    /**
     * @param string $hash
     * @param Form\ResetPasswordFormData $data
     * @return void
     * @throws HashExpiredException
     * @throws UserNotFoundException
     */
    public function resetPassword(string $hash, Form\ResetPasswordFormData $data):void
    {
        $user = $this->checkForgotPasswordHash($hash);
        $user->update([
            'password' => $this->passwords->hash($data->password),
            'forgot_password_hash' => null,
            'forgot_password_expire' => null,
        ]);
    }
}